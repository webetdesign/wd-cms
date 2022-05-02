<?php

namespace WebEtDesign\CmsBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WebEtDesign\CmsBundle\CmsTemplate\AbstractPage;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Factory\TemplateFactoryInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class PageAdminListener
{
    protected TemplateFactoryInterface $templateFactory;
    protected EntityManager            $em;
    protected Router                   $router;
    protected Filesystem               $fs;
    protected KernelInterface          $kernel;
    protected string                   $routeClass;
    protected array                    $configCms;
    protected ContainerInterface       $container;
    protected ParameterBagInterface    $parameterBag;

    public function __construct(
        TemplateFactoryInterface $templateFactory,
        EntityManager $em,
        Router $router,
        Filesystem $fs,
        KernelInterface $kernel,
        ParameterBagInterface $parameterBag,
        ContainerInterface $container
    ) {
        $this->templateFactory = $templateFactory;
        $this->em              = $em;
        $this->router          = $router;
        $this->fs              = $fs;
        $this->kernel          = $kernel;
        $this->container       = $container;
        $this->parameterBag    = $parameterBag;
        $this->configCms       = $this->parameterBag->get('wd_cms.cms');
        $this->routeClass      = CmsRoute::class;
    }

    // create page form template configuration
    public function prePersist($event): void
    {
        $page = $event->getObject();

        if (!$page instanceof CmsPage) {
            return;
        }
        $config = $this->templateFactory->get($page->getTemplate());

        if (!$page->dontImportContent) {
            // hydrate content
            foreach ($config->getBlocks() as $block) {
                if (!$page->getContent($block->getCode())) {
                    $CmsContent = new CmsContent();
                    $CmsContent->setCode($block->getCode());
                    $CmsContent->setLabel($block->getLabel());
                    $CmsContent->setType($block->getType());
                    $page->addContent($CmsContent);
                }
            }
        }
    }

    // create route from template configuration
    public function postPersist($event): void
    {
        $page = $event->getObject();

        if (!$page instanceof CmsPage) {
            return;
        }

        $config = $this->templateFactory->get($page->getTemplate());

        if ($config->isSection() || $page->getRoute() != null || !$page->initRoute) {
            return;
        }

        $this->createRoute($config, $page);

        $this->warmUpRouteCache();
    }

    // clear cache routing on update
    public function postUpdate($event): void
    {
        $page = $event->getObject();

        if (!$page instanceof CmsPage) {
            return;
        }

        $config = $this->templateFactory->get($page->getTemplate());

        if (!$config->isSection() && $page->getRoute() === null && $page->initRoute) {
            $this->createRoute($config, $page);
        }

        if ($config->isSection() && $page->getRoute() !== null) {
            $route = $page->getRoute();
            $page->setRoute(null);
            $this->em->remove($route);
        }

        $this->warmUpRouteCache();
    }

    // remove cache routing file and warmup cache
    protected function warmUpRouteCache(): void
    {
        $cacheDir = $this->kernel->getCacheDir();

        foreach (['matcher_class', 'generator_class'] as $option) {
            $className = $this->router->getOption($option);
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $className . '.php';
            $this->fs->remove($cacheFile);
        }

        $this->router->warmUp($cacheDir);
    }

    protected function createRoute(AbstractPage $config, CmsPage $page): void
    {
        $paramString  = '';
        $defaults     = [];
        $requirements = [];

        $route = $config->getRoute();

        if (!$route) {
            return;
        }

        foreach ($route->getAttributes() as $attribute) {
            $paramString                         .= "/{" . $attribute->getName() . "}";
            $defaults[$attribute->getName()]     = $attribute->getDefault();
            $requirements[$attribute->getName()] = $attribute->getRequirement();
        }

        $defaultName = $route->getName();

        // hydrate route
        $CmsRoute = new $this->routeClass();
        if ($this->configCms['multilingual']) {
            $routeName = $defaultName ? sprintf('%s_%s', $page->getSite()->getLocale(),
                $defaultName) : sprintf('%s_cms_route_%s', $page->getSite()->getLocale(),
                $page->getId());
        } else {
            $routeName = $defaultName ? sprintf('%s', $defaultName) : sprintf('cms_route_%s',
                $page->getId());
        }

        // Pour éviter le problème de doublon de route
        $exists = $this->em->getRepository(CmsRoute::class)->findBy(['name' => $routeName]);

        if (is_array($exists) && count($exists) > 0) {
            $routeName .= '_' . uniqid();
        }

        $CmsRoute->setName($routeName);

        if (!empty($route->getController())) {
            $controller = $route->getController();
            $controller .= '::' . (!empty($route->getAction()) ? $route->getAction() : '__invoke');
            $CmsRoute->setController($controller);
        }

        if ($route->getPath()) {
            $path = $route->getPath();
        } else {
            $path = ($page->rootPage ? '/' : '/' . $page->getSlug()) . $paramString;
        }


        $CmsRoute->setMethods($route->getMethods());
        $CmsRoute->setPath($path);
        $CmsRoute->setDefaults(json_encode($defaults));
        $CmsRoute->setRequirements(json_encode($requirements));
        $CmsRoute->setPage($page);

        // link route to current page
        $page->setRoute($CmsRoute);

        // persist route
        $this->em->persist($CmsRoute);
        $this->em->flush();
    }

    protected function moveItems(EntityManager $em, $submittedObject): void
    {
        $cmsRepo = $em->getRepository(CmsMenuItem::class);

        switch ($submittedObject->getMoveMode()) {
            case 'persistAsFirstChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsFirstChildOf($submittedObject,
                        $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsLastChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsLastChildOf($submittedObject,
                        $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsNextSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsNextSiblingOf($submittedObject,
                        $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsPrevSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsPrevSiblingOf($submittedObject,
                        $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsPrevSibling($submittedObject);
                }
                break;
        }

        $em->flush();
    }

}
