<?php

namespace WebEtDesign\CmsBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Routing\RouterInterface;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
use Doctrine\ORM\EntityManager;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\Router;

class CmsMenuAdminBuilder
{
    private $em;

    private $factory;

    private $repo;

    /** @var Router */
    private $router;

    private $admin;

    /**
     * CmsMenuBuilder constructor.
     * @param FactoryInterface $factory
     * @param EntityManager $entityManager
     * @param Router $router
     */
    public function __construct(FactoryInterface $factory, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->em      = $entityManager;
        $this->repo    = $this->em->getRepository(CmsMenuItem::class);
        $this->router  = $router;
        $this->factory = $factory;
    }

    public function cmsAdminMenu(array $options)
    {
        $rootItem = $options['item'];
        $repo = $this->em->getRepository(CmsMenuItem::class);
        $this->admin = $options['admin'] ?? null;

        /** @var MenuItem $menu */
        $menu     = $this->factory->createItem('root', [
            'childrenAttributes' => [
                'class' => 'StackedList',
                'data-parent-id' => $rootItem->getId()
            ]
        ]);
        $this->buildNodes($menu, $repo->children($rootItem, true));

        return $menu;
    }

    public function buildNodes(ItemInterface $menu, $items)
    {
        /** @var CmsMenuItem $child */
        foreach ($items as $child) {
            $children = $this->repo->getChildren($child, true);

            $childItem = $menu->addChild($child->getName());
            $childItem->setAttributes([
                'class'   => 'StackedListItem item-data',
                'data-id' => $child->getId(),
                'data-edit-url' => $this->admin->generateUrl('edit', ['id' => $child->getId()]),
                'data-add-url' => $this->admin->generateUrl('create', ['target' => $child->getId()])
            ]);
            $childItem->setChildrenAttributes([
                'class'          => 'StackedList',
                'data-parent-id' => $child->getId()
            ]);

            $this->buildNodes($childItem, $children);
        }
        $ghost = $menu->addChild('ghost');
        $ghost->setLabel('Drop child here');
        $ghost->setAttribute('class', 'StackedListItem StackedListGhost');
    }

}
