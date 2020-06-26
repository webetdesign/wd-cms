<?php


namespace WebEtDesign\CmsBundle\Command;


use Doctrine\ORM\EntityManager;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;

class CmsDuplicateSiteCommand extends Command
{
    protected static $defaultName = 'cms:duplicate:site';

    protected $em;
    /**
     * @var SymfonyStyle
     */
    private $io;
    private $pageRepository;
    private $siteRepository;

    /**
     * @inheritDoc
     */
    public function __construct(
        ?string $name = null,
        EntityManager $em,
        CmsPageRepository $pageRepository,
        CmsSiteRepository $siteRepository
    ) {
        $this->em = $em;
        parent::__construct($name);
        $this->pageRepository = $pageRepository;
        $this->siteRepository = $siteRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Duplicate site for an other locale')
            //            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            //            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $sites       = $this->siteRepository->findAll();
        $defaultSite = $this->siteRepository->getDefault();

        $choices = [];
        foreach ($sites as $site) {
            $choices[$site->getId()] = $site->__toString();
        }

        $choice = $this->io->choice('Site ? ', $choices, $defaultSite->getId());

        $site = $this->siteRepository->find(array_search($choice, $choices));

        $newLocale = $this->io->ask('New locale ?', null, function ($locale) use ($site) {
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
            $this->io->note('A site already exist with locale "' . $newLocale . '"');
            $doClean = $this->io->confirm('Would you like to remove exiting pages ?', false);
        } else {
            $newSite = new CmsSite();
            $newSite->setLocale($newLocale);
            $newSite->setHost($site->getHost());
            $newSite->setHostMultilingual($site->isHostMultilingual());
            $newSite->setDefault(false);

            $label = $this->io->ask('Label', $site->getLabel());
            $newSite->setLabel($label);
            $flag = $this->io->ask('Flag icon (optional)', $newLocale);
            $newSite->setFlagIcon($flag);

            $this->em->persist($newSite);
            $this->em->flush();
        }


        $this->duplicate($site, $newSite, $doClean);
    }

    private function duplicate(?CmsSite $site, ?CmsSite $newSite, bool $doClean)
    {
        $home    = $site->getRootPage();
        $newHome = $newSite->getRootPage();
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
        $this->em->flush();
    }

    protected function processPages(CmsSite $site, CmsPage $reference, CmsPage $newParent)
    {
        /** @var CmsPage $referenceChild */
        foreach ($reference->getChildrenLeft() as $referenceChild) {
            $newPage = $this->duplicatePage($site, $referenceChild);
            $this->processPages($site, $referenceChild, $newPage);
            $this->pageRepository->persistAsLastChildOf($newPage, $newParent);
        }
    }

    private function duplicatePage(CmsSite $site, CmsPage $page)
    {
        $newPage = new CmsPage();

        $newPage->setTemplate($page->getTemplate());
        $newPage->setTitle($page->getTitle());
        $newPage->setActive($page->getActive());
        $newPage->setSite($site);
        $newPage->addCrossSitePage($page);
        $newPage->initRoute = false;

        if (count($page->getDeclinations()) > 0) {
            $this->duplicateDeclinations($page, $newPage);
        }

        $this->duplicateRoute($page, $newPage);

        $this->em->persist($newPage);
        return $newPage;
    }

    public function processRouteName(CmsPage $page, $newLocale)
    {
        $route = $page->getRoute();

        $name   = $page->getRoute()->getName();
        $locale = $page->getSite()->getLocale();

        return preg_replace('/^' . $locale . '_/', $newLocale . '_', $name);
    }

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
