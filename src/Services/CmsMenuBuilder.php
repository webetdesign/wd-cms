<?php

namespace WebEtDesign\CmsBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\User;
use HttpInvalidParamException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
use Doctrine\ORM\EntityManager;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\RequestStack;

class CmsMenuBuilder
{
    /** @var EntityManagerInterface */
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
    private $configMenu;
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * CmsMenuBuilder constructor.
     * @param FactoryInterface $factory
     * @param EntityManagerInterface $entityManager
     * @param RouterInterface $router
     * @param TokenStorageInterface $storage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param RequestStack $requestStack
     * @param $configMenu
     */
    public function __construct(
        FactoryInterface $factory,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        TokenStorageInterface $storage,
        AuthorizationCheckerInterface $authorizationChecker,
        RequestStack $requestStack,
        ContainerInterface $container,
        $configMenu
    ) {
        $this->em                   = $entityManager;
        $this->router               = $router;
        $this->factory              = $factory;
        $this->storage              = $storage;
        $this->authorizationChecker = $authorizationChecker;
        $this->requestStack         = $requestStack;
        $this->configMenu           = $configMenu;
        $this->container            = $container;
    }

    public function cmsMenu(array $options)
    {
        $page         = $options['page'] ?? null;
        $code         = $options['code'];
        $parentActive = $options['parentActive'] ?? false;
        $activeClass  = $options['activeClass'] ?? 'active';
        $mainClass    = $options['classes'] ?? false;

        $repo = $this->em->getRepository('WebEtDesignCmsBundle:CmsMenuItem');
        if ($page) {
            $locale = $page->getSite()->getLocale();
            $menu = $this->em->getRepository('WebEtDesignCmsBundle:CmsMenu')->findOneBy([
                'code' => $code,
                'site' => $page->getSite()
            ]);
        } else {
            $menu = $this->em->getRepository('WebEtDesignCmsBundle:CmsMenu')->findOneBy(['code' => $code]);
        }

        if (!$menu) {
            return $this->factory->createItem('root');
        }

        /*
         * Cette query recupère tous les enfants de chaque noeud
         * afin de ne pas faire une requette à chaque appelle à la fonction getChildren()
         */
        $nodes = $repo->flatNodes($menu);

        $root = $this->factory->createItem('root');
        if ($mainClass) {
            $root->setChildrenAttribute('class', $mainClass);
        }
        $children = isset($menu->getChildren()[0]) ? $menu->getChildren()[0]->getChildren() : [];
        $this->buildNodes($root, $children, $parentActive, $activeClass, $locale ?? null);

        return $root;
    }

    public function buildNodes(ItemInterface $menu, $items, $parentActive, $activeClass, $locale)
    {

        /** @var User $user */
        if ($this->storage->getToken() != null) {
            $user = $this->storage->getToken()->getUser();
        } else {
            $user = null;
        }

        /** @var CmsMenuItem $child */
        foreach ($items as $child) {
            if (!$child->isVisible()) {
                continue;
            }
            if ($child->getRole() && !$this->authorizationChecker->isGranted($child->getRole())) {
                continue;
            }
            if ($child->getConnected() == 'ONLY_LOGIN' && $user === 'anon.') {
                continue;
            }
            if ($child->getConnected() == 'ONLY_LOGOUT' && $user !== 'anon.') {
                continue;
            }

            $children  = $child->getChildren();
            $childItem = $menu->addChild($child->getName());

            $childItemClass = '';
            if ($child->getClasses()) {
                $childItemClass .= $child->getClasses() . ' ';
            }

            if (sizeof($children) == 0 || (sizeof($children) > 0 && $parentActive)) {
                switch ($child->getLinkType()) {
                    case CmsMenuLinkTypeEnum::CMS_PAGE:
                        if ($child->getPage()) {
                            if (!$child->getPage()->isActive()) {
                                $menu->removeChild($child->getName());
                                continue 2;
                            }
                            $childItem->setExtra('page', $child->getPage());
                            if ($this->isActive($child)) {
                                $childItemClass .= $activeClass;
                            }
                            $route = $child->getPage()->getRoute();
                            if ($route) {
                                if ($route->isDynamic()) {
                                    $params = json_decode($child->getParams(), true) ?: [];
                                    try {
                                        $childItem->setUri($this->router->generate($route->getName(),
                                            $params));
                                    } catch (InvalidParameterException $exception) {
                                    }
                                } else {
                                    $childItem->setUri($this->router->generate($route->getName()));
                                }
                            }
                        }
                        break;
                    case CmsMenuLinkTypeEnum::ROUTENAME:
                        if (!empty($child->getLinkValue() && (null === $this->router->getRouteCollection()->get($child->getLinkValue())) ? false : true)) {
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
                    case CmsMenuLinkTypeEnum::SERVICE:
                        if (isset($this->configMenu[$child->getLinkValue()])) {
                            $service = $this->container->get($this->configMenu[$child->getLinkValue()]['service']);
                            $service->build($childItem, $locale);
                        }
                        break;

                }
            }
            if (count($children) > 0) {
                $this->buildNodes($childItem, $children, $parentActive, $activeClass, $locale);
            }

            $childItem->setAttribute('class', $childItemClass);
            if ($child->isBlank()) {
                $childItem->setLinkAttribute('target', '_blank');
            }
        }
    }

    public function isActive(CmsMenuItem $item)
    {
        $request         = $this->requestStack->getCurrentRequest();
        $activeRouteName = $request->get('_route');
        if (!$item->getPage()->getRoute()) {
            return false;
        }
        $escapeString = preg_quote($item->getPage()->getRoute()->getPath(), '/');

        $active = false;

        if ($item->getPage()->getRoute()->getPath() != '/') {
            if (preg_match('/' . $escapeString . '/', $request->getPathInfo())) {
                $active = true;
            }
        }

        if ($item->getPage()->getRoute()->getName() == $activeRouteName) {
            $active = true;
        }

        return $active;
    }

}
