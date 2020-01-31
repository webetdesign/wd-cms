<?php

namespace WebEtDesign\CmsBundle\EventListener;

use App\Utils\ArrayUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsContentTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Services\TemplateProvider;
use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\Event\PersistenceEvent;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class PageAdminListener
{
    protected $provider;
    protected $em;
    protected $router;
    protected $fs;
    protected $kernel;
    protected $routeClass;
    protected $configCms;
    protected $configCustomContent;
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(
        TemplateProvider $provider,
        EntityManager $em,
        Router $router,
        Filesystem $fs,
        KernelInterface $kernel,
        $routeClass,
        $configCms,
        $configCustomContent,
        ContainerInterface $container
    ) {
        $this->provider            = $provider;
        $this->em                  = $em;
        $this->router              = $router;
        $this->fs                  = $fs;
        $this->kernel              = $kernel;
        $this->routeClass          = $routeClass;
        $this->configCms           = $configCms;
        $this->configCustomContent = $configCustomContent;
        $this->container           = $container;
    }

    // create page form template configuration
    public function prePersist($event)
    {
        $page = $event->getObject();

        if (!$page instanceof CmsPage) {
            return;
        }
        $config = $this->provider->getConfigurationFor($page->getTemplate());

        if (isset($config['association'])) {
            $page->setClassAssociation($config['association']['class']);
            $page->setQueryAssociation($config['association']['queryMethod']);
        }

        if (!$page->dontImportContent) {
            // hydrate content
            foreach ($config['contents'] as $content) {
                $CmsContent = new CmsContent();
                $CmsContent->setCode($content['code']);
                $CmsContent->setLabel($content['code'] ?? $content['label']);
                $CmsContent->setType($content['type']);
                $CmsContent->setHelp($content['help'] ?? null);
                $page->addContent($CmsContent);
            }
        }
    }

    // create route from template configuration
    public function postPersist($event)
    {
        $page = $event->getObject();

        if (!$page instanceof CmsPage) {
            return;
        }

        if ($this->configCms['menuByPage']) {
            $this->createMenuItem($event->getEntityManager(), $page);
        }

        $config = $this->provider->getConfigurationFor($page->getTemplate());

        if ($config['disableRoute'] || $page->getRoute() != null) {
            return;
        }

        $this->createRoute($config, $page);


        $this->warmUpRouteCache();
    }

    // clear cache routing on update
    public function postUpdate($event)
    {
        $page = $event->getObject();

        if (!$page instanceof CmsPage) {
            return;
        }

        $config = $this->provider->getConfigurationFor($page->getTemplate());

        if (!$config['disableRoute'] && $page->getRoute() === null) {
            $this->createRoute($config, $page);
        }

        if ($config['disableRoute'] && $page->getRoute() !== null) {
            $route = $page->getRoute();
            $page->setRoute(null);
            $this->em->remove($route);
        }

        if ($this->configCms['menuByPage']) {
            $this->moveMenuItem($event->getEntityManager(), $page);
        }

        $this->warmUpRouteCache();
    }

    public function preRemove($event)
    {
        $page = $event->getObject();

        if (!$page instanceof CmsPage) {
            return;
        }

        $em       = $event->getEntityManager();
        $menuRepo = $em->getRepository(CmsMenuItem::class);
        $menuItem = $menuRepo->getPageArboMenuItem($page);

        if ($menuItem) {
            $em->remove($menuItem);
        }

    }

    protected function moveMenuItem(EntityManager $em, CmsPage $page)
    {
        if ($page->getLvl() === 0) {
            return;
        }
        /** @var CmsMenuItem $menu */
        $menuRepo = $em->getRepository(CmsMenuItem::class);
        $pageRepo = $em->getRepository('WebEtDesignCmsBundle:CmsPage');
        $menu     = $menuRepo->getPageArboMenuItem($page);

        if (!$menu) {
            return false;
        }

        $pagePrevSiblings = $pageRepo->getPrevSiblings($page);
        $prevPage         = isset($pagePrevSiblings[array_key_last($pagePrevSiblings)]) ? $pagePrevSiblings[array_key_last($pagePrevSiblings)] : null;

        $menuPrevSiblings = $menuRepo->getPrevSiblings($menu);
        $prevMenu         = isset($menuPrevSiblings[array_key_last($menuPrevSiblings)]) ? $menuPrevSiblings[array_key_last($menuPrevSiblings)] : null;
        $prevMenuPage     = $prevPage !== null ? $prevMenu->getPage() : null;

        if ($menu->getParent()->getPage() !== $page->getParent() || $prevMenuPage !== $prevPage) {
            if ($page->getParent()->isRoot()) {
                $target = $menu->getRoot();
                $menuRepo->persistAsFirstChildOf($menu, $target);
                $em->flush();
            } elseif ($prevPage !== null) {
                $target = $menuRepo->getPageArboMenuItem($prevPage);
                $menuRepo->persistAsNextSiblingOf($menu, $target);
                $em->flush();
            } else {
                $target = $menuRepo->getPageArboMenuItem($page->getParent());
                $menuRepo->persistAsFirstChildOf($menu, $target);
                $em->flush();
            }

        }
    }

    protected function createMenuItem(EntityManagerInterface $em, CmsPage $page)
    {
        $menuRepo = $em->getRepository('WebEtDesignCmsBundle:CmsMenuItem');
        /** @var CmsMenu $menu */
        $menu = $page->getSite()->getMenuArbo();
        if ($menu) {
            $menuItem = new CmsMenuItem();

            $menuItem->setIsVisible($page->isActive());
            $menuItem->setLinkType(CmsMenuLinkTypeEnum::CMS_PAGE);
            $menuItem->setPage($page);
            $menuItem->setName($page->getTitle());
            $menuItem->setMenu($menu);
            $em->persist($menuItem);

            if ($page->getMoveTarget()->isRoot()) {
                $target = $menu->getChildren()[0];
            } else {
                $target = $menuRepo->getPageArboMenuItem($page->getMoveTarget());
            }
            $menuItem->setMoveMode($page->getMoveMode());
            $menuItem->setMoveTarget($target);
            $this->moveItems($em, $menuItem);
        }
    }

    // remove cache routing file and warmup cache
    protected function warmUpRouteCache()
    {
        $cacheDir = $this->kernel->getCacheDir();

        foreach (['matcher_cache_class', 'generator_cache_class'] as $option) {
            $className = $this->router->getOption($option);
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $className . '.php';
            $this->fs->remove($cacheFile);
        }

        $this->router->warmUp($cacheDir);
    }

    protected function createRoute($config, $page)
    {
        $paramString  = '';
        $defaults     = [];
        $requirements = [];
        foreach ($config['params'] as $param => $attributes) {
            $paramString          .= "/{" . $param . "}";
            $defaults[$param]     = $attributes['default'];
            $requirements[$param] = $attributes['requirement'];
        }

        // hydrate route
        $CmsRoute = new $this->routeClass();
        $CmsRoute->setName(sprintf('cms_route_%s', $page->getId()));

        if ($config['controller'] && $config['action']) {
            $CmsRoute->setController(sprintf('%s::%s', $config['controller'], $config['action']));
        }

        $CmsRoute->setMethods($config['methods']);
        $CmsRoute->setPath($page->rootPage ? '/' : '/' . $page->getSlug() . $paramString);
        $CmsRoute->setDefaults(json_encode($defaults));
        $CmsRoute->setRequirements(json_encode($requirements));
        $CmsRoute->setPage($page);

        // link route to current page
        $page->setRoute($CmsRoute);

        // persist route
        $this->em->persist($CmsRoute);
        $this->em->flush();
    }

    protected function moveItems(EntityManager $em, $submittedObject)
    {
        $cmsRepo = $em->getRepository(CmsMenuItem::class);

        switch ($submittedObject->getMoveMode()) {
            case 'persistAsFirstChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsFirstChildOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsLastChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsLastChildOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsNextSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsNextSiblingOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsPrevSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsPrevSiblingOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsPrevSibling($submittedObject);
                }
                break;
        }

        $em->flush();
    }


}
