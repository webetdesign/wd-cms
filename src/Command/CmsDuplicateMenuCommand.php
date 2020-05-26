<?php


namespace WebEtDesign\CmsBundle\Command;


use Doctrine\ORM\EntityManager;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Repository\CmsMenuItemRepository;
use WebEtDesign\CmsBundle\Repository\CmsMenuRepository;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;

class CmsDuplicateMenuCommand extends Command
{
    protected static $defaultName = 'cms:duplicate:menu';

    protected $em;
    /**
     * @var SymfonyStyle
     */
    private $io;
    private $siteRepository;
    private $menuRepository;
    private $menuItemRepository;

    /**
     * @inheritDoc
     */
    public function __construct(
        ?string $name = null,
        EntityManager $em,
        CmsMenuRepository $menuRepository,
        CmsMenuItemRepository $menuItemRepository,
        CmsSiteRepository $siteRepository
    ) {
        $this->em = $em;
        parent::__construct($name);
        $this->siteRepository = $siteRepository;
        $this->menuRepository = $menuRepository;
        $this->menuItemRepository = $menuItemRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Duplicate Menu for an other locale')
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

        $choice = $this->io->choice('Copy menu from site ? ', $choices, $defaultSite->getId());
        $siteFrom = $this->siteRepository->find(array_search($choice, $choices));

        $choice = $this->io->choice('to site ? ', $choices);
        $siteTo = $this->siteRepository->find(array_search($choice, $choices));

        $choices = [];
        foreach ($siteFrom->getMenu() as $menu) {
            $choices[$menu->getId()] = $menu->getLabel();
        }

        $choice = $this->io->choice('Menu to copy ? ', $choices, $defaultSite->getId());
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

    private function duplicate(CmsMenuItem $root, CmsMenuItem $newRoot)
    {
        /** @var CmsMenuItem $ref */
        foreach ($root->getChildrenLeft() as $ref) {
            $item = new CmsMenuItem();
            $item->setMenu($newRoot->getMenu());
            $item->setName($ref->getName());
            $item->setClasses($ref->getClasses());
            $item->setLinkType($ref->getLinkType());
            $item->setLinkValue($item->getLinkValue());
            $item->setIsVisible($ref->isVisible());
            $item->setBlank($item->isBlank());
            $item->setAnchor($ref->getAnchor());
            $item->setParams($ref->getParams());

            $this->em->persist($item);

            $this->menuItemRepository->persistAsLastChildOf($item, $newRoot);

            $this->duplicate($ref, $item);
        }
    }

}
