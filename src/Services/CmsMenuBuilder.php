<?php

namespace WebEtDesign\CmsBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\User;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
use Doctrine\ORM\EntityManager;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\RequestStack;

class CmsMenuBuilder
{
    private $em;

    private $factory;

    private $repo;

    /** @var Router */
    private $router;

    /** @var TokenStorageInterface */
    private $storage;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var RequestStack */
    private $requestStack;

    /**
     * CmsMenuBuilder constructor.
     * @param FactoryInterface $factory
     * @param EntityManager $entityManager
     * @param Router $router
     */
    public function __construct(
        FactoryInterface $factory,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        TokenStorageInterface $storage,
        AuthorizationCheckerInterface $authorizationChecker,
        RequestStack $requestStack
    ) {
        $this->em                   = $entityManager;
        $this->repo                 = $this->em->getRepository('WebEtDesignCmsBundle:CmsMenu');
        $this->router               = $router;
        $this->factory              = $factory;
        $this->storage              = $storage;
        $this->authorizationChecker = $authorizationChecker;
        $this->requestStack         = $requestStack;
    }

    public function __cmsMenu(array $options)
    {
        $menuRootName = $options['menuRootName'];
        $parentActive = $options['parentActive'] ?? false;

        $repo = $this->em->getRepository('WebEtDesignCmsBundle:CmsMenu');


        $menu     = $this->factory->createItem('root');
        $rootItem = $repo->getRootByName($menuRootName);
        $this->buildNodes($menu, $repo->children($rootItem, true), $parentActive);

        return $menu;
    }

    public function cmsMenu(array $options)
    {
        $menuRootCode = $options['code'];
        $parentActive = $options['parentActive'] ?? false;

        $repo = $this->em->getRepository('WebEtDesignCmsBundle:CmsMenu');

        $menu     = $this->factory->createItem('root');
        $rootItem = $repo->getByCode($menuRootCode);
        $menu->setChildrenAttribute('class', $rootItem->getClasses());
        $this->buildNodes($menu, $repo->children($rootItem, true), $parentActive);

        return $menu;
    }

    public function buildNodes(ItemInterface $menu, $items, $parentActive)
    {
        /** @var User $user */
        $user = $this->storage->getToken()->getUser();

        /** @var CmsMenu $child */
        foreach ($items as $child) {
            if ($child->getRole() && !$this->authorizationChecker->isGranted($child->getRole())) {
                continue;
            }
            if ($child->getConnected() == 'ONLY_LOGIN' && $user === 'anon.') {
                continue;
            }
            if ($child->getConnected() == 'ONLY_LOGOUT' && $user !== 'anon.') {
                continue;
            }

            $children  = $this->repo->getChildren($child, true);
            $childItem = $menu->addChild($child->getName());

            $childItemClass = '';
            if ($child->getClasses()) {
                $childItemClass .= $child->getClasses() . ' ';
            }

            if (sizeof($children) == 0 || (sizeof($children) > 0 && $parentActive)) {
                switch ($child->getLinkType()) {
                    case CmsMenuLinkTypeEnum::CMS_PAGE:
                        if ($child->getPage()) {
                            if ($this->isActive($child)) {
                                $childItemClass .= 'active ';
                            }
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

            $childItem->setAttribute('class', $childItemClass);
        }
    }

    public function isActive(CmsMenu $item)
    {

        $request = $this->requestStack->getCurrentRequest();
        $activeRouteName = $request->get('_route');

        $escapeString = preg_quote($item->getPage()->getRoute()->getPath(), '/');

        $active = false;

        if ($item->getPage()->getRoute()->getPath() != '/') {
            if (preg_match('/'. $escapeString .'/', $request->getPathInfo())) {
                $active = true;
            }
        }

        if ($item->getPage()->getRoute()->getName() == $activeRouteName) {
            $active = true;
        }

        return $active;
    }

}
