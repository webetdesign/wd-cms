<?php

namespace WebEtDesign\CmsBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
use Doctrine\ORM\EntityManager;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\Router;

class CmsMenuBuilder
{
    private $em;

    private $factory;

    private $repo;

    /** @var Router */
    private $router;

    /**
     * CmsMenuBuilder constructor.
     * @param FactoryInterface $factory
     * @param EntityManager $entityManager
     * @param Router $router
     */
    public function __construct(FactoryInterface $factory, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->em = $entityManager;
        $this->repo = $this->em->getRepository('WebEtDesignCmsBundle:CmsMenu');
        $this->router = $router;
        $this->factory = $factory;
    }


    public function cmsMenu(array $options)
    {
        $menuRootName = $options['menuRootName'];
        $parentActive = $options['parentActive'] ?? false;

        $repo = $this->em->getRepository('WebEtDesignCmsBundle:CmsMenu');


        $menu = $this->factory->createItem('root');
        $rootItem = $repo->getRootByName($menuRootName);
        $this->buildNodes($menu, $repo->children($rootItem, true), $parentActive);

        return $menu;
    }

    public function buildNodes(ItemInterface $menu, $items, $parentActive)
    {
        /** @var CmsMenu $child */
        foreach ($items as $child) {
            $children = $this->repo->getChildren($child, true);
            $childItem = $menu->addChild($child->getName());
            if (sizeof($children) == 0 || (sizeof($children) > 0 && $parentActive)) {
                switch ($child->getLinkType()) {
                    case CmsMenuLinkTypeEnum::CMS_PAGE:
                        if ($child->getPage()) {
                            $childItem->setUri($this->router->generate($child->getPage()->getRoute()->getName()));
                        }
                        break;
                    case CmsMenuLinkTypeEnum::ROUTENAME:
                        if (!empty($child->getLinkValue())) {
                            $childItem->setUri($this->router->generate($child->getLinkValue()));
                        }
                        break;
                    case CmsMenuLinkTypeEnum::URL:
                        $url = $child->getLinkValue();
                        if (!preg_match('/^https?:\/\//', $url)) {
                            $url = 'http://' . $url;
                        }
                        $childItem->setUri($url);
                        break;
                    case CmsMenuLinkTypeEnum::PATH:
                        $childItem->setUri($child->getLinkValue());
                        break;
                }
            }
            if (sizeof($children) > 0) {
                $this->buildNodes($childItem, $children, $parentActive);
            }
        }
    }

}
