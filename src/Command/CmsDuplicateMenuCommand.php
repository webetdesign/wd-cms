<?php
declare(strict_types=1);


namespace WebEtDesign\CmsBundle\Command;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuTypeEnum;
use WebEtDesign\CmsBundle\Repository\CmsMenuItemRepository;
use WebEtDesign\CmsBundle\Repository\CmsMenuRepository;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;

class CmsDuplicateMenuCommand extends Command
{
    protected EntityManager $em;
    private CmsSiteRepository $siteRepository;
    private CmsMenuRepository $menuRepository;
    private CmsMenuItemRepository $menuItemRepository;
    private CmsPageRepository $cmsPageRepository;

    /**
     * @inheritDoc
     */
    public function __construct(
        EntityManager $em,
        CmsMenuRepository $menuRepository,
        CmsMenuItemRepository $menuItemRepository,
        CmsSiteRepository $siteRepository,
        CmsPageRepository $cmsPageRepository,
        ?string $name = null
    ) {
        $this->em = $em;
        parent::__construct($name);
        $this->siteRepository = $siteRepository;
        $this->menuRepository = $menuRepository;
        $this->menuItemRepository = $menuItemRepository;
        $this->cmsPageRepository = $cmsPageRepository;
    }

    protected function configure(): void
    {
        $this
            ->setName('cms:duplicate:menu')
            ->setDescription('Duplicate Menu for an other locale')
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

        $choice = $io->choice('Copy menu from site ? ', $choices, $defaultSite->getId());
        $siteFrom = $this->siteRepository->find(array_search($choice, $choices));

        $choice = $io->choice('to site ? ', $choices);
        $siteTo = $this->siteRepository->find(array_search($choice, $choices));

        $choices = [];
        foreach ($siteFrom->getMenu() as $menu) {
            $choices[$menu->getId()] = $menu->getLabel();
        }

        $choice = $io->choice('Menu to copy ? ', $choices, $defaultSite->getId());
        $menu = $this->menuRepository->find(array_search($choice, $choices));

        $newMenu = new CmsMenu();
        $newMenu->setSite($siteTo);
        $newMenu->setLabel($menu->getLabel());
        $newMenu->setCode($menu->getCode());
        $newMenu->setType(CmsMenuTypeEnum::DEFAULT);

        $newRoot = new CmsMenuItem();
        $newRoot->setName('root ' . $menu->getSite() . ' ' . $menu->getLabel());
        $newRoot->setMenu($newMenu);

        $this->em->persist($newMenu);
        $this->em->persist($newRoot);

        $root = $menu->getRoot();

        $this->duplicate($root, $newRoot);

        $this->em->flush();
    }

    /**
     * @param CmsMenuItem $root
     * @param CmsMenuItem $newRoot
     * @throws ORMException
     * @author Benjamin Robert
     */
    private function duplicate(CmsMenuItem $root, CmsMenuItem $newRoot): void
    {
        /** @var CmsMenuItem $ref */
        foreach ($root->getChildrenLeft() as $ref) {
            $item = new CmsMenuItem();
            $item->setMenu($newRoot->getMenu());
            $item->setName($ref->getName());
            $item->setLiClass($ref->getLiClass());
            $item->setUlClass($ref->getUlClass());
            $item->setLinkClass($ref->getLinkClass());
            $item->setLinkType($ref->getLinkType());
            $item->setLinkValue($item->getLinkValue());
            $item->setIsVisible($ref->isVisible());
            $item->setBlank($item->isBlank());
            $item->setAnchor($ref->getAnchor());
            $item->setParams($ref->getParams());

            if ($ref->getPage()?->getRoute() != null) {
                $fromRouteName = $ref->getPage()->getRoute()->getName();
                $fromLocal = $ref->getPage()->getSite()->getLocale();
                $toLocal = $newRoot->getSite()->getLocale();
                $toRouteName = $toLocal . substr($fromRouteName, strlen($fromLocal) , strlen($fromRouteName) - 1);
                $page = $this->cmsPageRepository->findPageByRouteName($toRouteName);
                $item->setPage($page);
            }

            $this->em->persist($item);

            $this->menuItemRepository->persistAsLastChildOf($item, $newRoot);

            $this->duplicate($ref, $item);
        }
    }

}
