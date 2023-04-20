<?php
declare(strict_types=1);


namespace WebEtDesign\CmsBundle\Command;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;

class CmsDuplicateSiteCommand extends Command
{
    protected EntityManager $em;
    private CmsPageRepository $pageRepository;
    private CmsSiteRepository $siteRepository;

    /**
     * @inheritDoc
     */
    public function __construct(
        EntityManager $em,
        CmsPageRepository $pageRepository,
        CmsSiteRepository $siteRepository,
        ?string $name = null
    ) {
        $this->em = $em;
        parent::__construct($name);
        $this->pageRepository = $pageRepository;
        $this->siteRepository = $siteRepository;
    }

    protected function configure()
    {
        $this
            ->setName('cms:duplicate:site')
            ->setDescription('Duplicate site for an other locale')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws ORMException
     * @throws OptimisticLockException
     * @author Benjamin Robert
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $sites       = $this->siteRepository->findAll();
        $defaultSite = $this->siteRepository->getDefault();

        $choices = [];
        foreach ($sites as $site) {
            $choices[$site->getId()] = $site->__toString();
        }

        $choice = $io->choice('Site ? ', $choices, $defaultSite->getId());

        $site = $this->siteRepository->find(array_search($choice, $choices));

        $newLocale = $io->ask('New locale ?', null, function ($locale) use ($site) {
            if (empty($locale)) {
                throw new RuntimeException('New locale can not be empty');
            }
            if ($locale === $site->getLocale()) {
                throw new RuntimeException('You can\'t duplicate a site with the same locale');
            }

            return $locale;
        });

        $newSite = $this->siteRepository->findOneBy(['locale' => $newLocale]);

        $doClean = false;
        if ($newSite) {
            $io->note('A site already exist with locale "' . $newLocale . '"');
            $doClean = $io->confirm('Would you like to remove exiting pages ?', false);
        } else {
            $newSite = new CmsSite();
            $newSite->setLocale($newLocale);
            $newSite->setHost($site->getHost());
            $newSite->setLocalhost($site->getLocalhost());
            $newSite->setHostMultilingual($site->isHostMultilingual());
            $newSite->setDefault(false);
            $newSite->setTemplateFilter($site->getTemplateFilter());
            $newSite->initMenu = true;
            $newSite->initPage = true;

            $label = $io->ask('Label', $site->getLabel());
            $newSite->setLabel($label);
            $flag = $io->ask('Flag icon (optional)', $newLocale);
            $newSite->setFlagIcon($flag);

            $this->em->persist($newSite);
            $this->em->flush();
        }

        $this->duplicate($site, $newSite, $doClean);

        return 0;
    }

    /**
     * @param CmsSite|null $site
     * @param CmsSite|null $newSite
     * @param bool $doClean
     * @throws ORMException
     * @throws OptimisticLockException
     * @author Benjamin Robert
     */
    private function duplicate(?CmsSite $site, ?CmsSite $newSite, bool $doClean)
    {
        $home    = $site->getRootPage();
        $newHome = $newSite->getRootPage();
        $newHome->setTitle($home->getTitle());
        $newHome->setTemplate($home->getTemplate());
        $newHome->getRoute()->setName($this->processRouteName($home, $newSite->getLocale()));
        $newHome->getRoute()->setController($home->getRoute()->getController());
        $newHome->getRoute()->setPath($home->getRoute()->getPath());

        if ($doClean) {
            foreach ($newHome->getChildrenRight() as $item) {
                $this->em->remove($item);
            }
            $this->em->flush();
        }

        $this->processPages($newSite, $home, $newHome);

        foreach ($newHome->getContents() as $content) {
            $newHome->removeContent($content);
        }
        $this->em->flush();

        foreach ($home->getContents() as $content) {
            /** @var CmsContent $content */
            $content = $content->clone();
            $newHome->addContent($content);
        }
        $this->em->flush();
    }

    /**
     * @param CmsSite $site
     * @param CmsPage $reference
     * @param CmsPage $newParent
     * @throws ORMException
     * @author Benjamin Robert
     */
    protected function processPages(CmsSite $site, CmsPage $reference, CmsPage $newParent)
    {
        /** @var CmsPage $referenceChild */
        foreach ($reference->getChildrenLeft() as $referenceChild) {
            $newPage = $this->duplicatePage($site, $referenceChild);
            $this->processPages($site, $referenceChild, $newPage);
            $this->pageRepository->persistAsLastChildOf($newPage, $newParent);
        }
    }

    /**
     * @param CmsSite $site
     * @param CmsPage $page
     * @return CmsPage
     * @throws ORMException
     * @author Benjamin Robert
     */
    private function duplicatePage(CmsSite $site, CmsPage $page)
    {
        $newPage = new CmsPage();

        $newPage->setTemplate($page->getTemplate());
        $newPage->setTitle($page->getTitle());
        $newPage->setActive($page->getActive());
        $newPage->setSite($site);
        $newPage->addCrossSitePage($page);
        $newPage->initRoute = false;

        foreach ($page->getContents() as $content) {
            /** @var CmsContent $content */
            $content = $content->clone();
            $newPage->addContent($content);
        }

        if (count($page->getDeclinations()) > 0) {
            $this->duplicateDeclinations($page, $newPage);
        }

        $this->duplicateRoute($page, $newPage);

        $this->em->persist($newPage);
        return $newPage;
    }

    public function processRouteName(CmsPage $page, $newLocale)
    {
        $name   = $page->getRoute()->getName();
        $locale = $page->getSite()->getLocale();

        return preg_replace('/^' . $locale . '_/', $newLocale . '_', $name);
    }

    /**
     * @param CmsPage $page
     * @param CmsPage $newPage
     * @throws ORMException
     * @author Benjamin Robert
     */
    private function duplicateDeclinations(CmsPage $page, CmsPage $newPage)
    {
        /** @var CmsPageDeclination $declination */
        foreach ($page->getDeclinations() as $declination) {
            $newD = new CmsPageDeclination();
            $newD->setTitle($declination->getTitle());
            $newD->setActive($declination->isActive());
            $newPage->addDeclination($newD);
            $this->em->persist($newD);
        }
    }

    /**
     * @param CmsPage $page
     * @param CmsPage $newPage
     * @throws ORMException
     * @author Benjamin Robert
     */
    private function duplicateRoute(CmsPage $page, CmsPage $newPage)
    {
        /** @var CmsRoute $route */
        $route = $page->getRoute();

        if (!$route) {
            return;
        }

        if ($newPage->getRoute() === null) {
            $newRoute = new CmsRoute();
        } else {
            $newRoute = $newPage->getRoute();
        }

        $newRoute->setName($this->processRouteName($page, $newPage->getSite()->getLocale()));
        $newRoute->setController($route->getController());
        $newRoute->setPath($route->getPath());
        $newRoute->setDefaults($route->getDefaults());
        $newRoute->setRequirements($route->getRequirements());
        $newRoute->setMethods($route->getMethods());

        $newPage->setRoute($newRoute);

        $this->em->persist($newRoute);
    }

}
