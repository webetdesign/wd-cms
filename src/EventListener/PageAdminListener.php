<?php

namespace WebEtDesign\CmsBundle\EventListener;

use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
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

    public function __construct(TemplateProvider $provider, EntityManager $em, Router $router, Filesystem $fs, KernelInterface $kernel, $routeClass)
    {
        $this->provider   = $provider;
        $this->em         = $em;
        $this->router     = $router;
        $this->fs         = $fs;
        $this->kernel     = $kernel;
        $this->routeClass = $routeClass;
    }

    // create page form template configuration
    public function buildPage(PersistenceEvent $event)
    {
        $page = $event->getObject();

        if (!$page instanceof CmsPage) {
            return;
        }

        $config = $this->provider->getConfigurationFor($page->getTemplate());

        if(isset($config['association'])) {
            $page->setClassAssociation($config['association']['class']);
            $page->setQueryAssociation($config['association']['queryMethod']);
        }

        // hydrate content
        foreach ($config['contents'] as $content) {
            $CmsContent = new CmsContent();
            $CmsContent->setCode($content['code'] ?? $content['label']);
            $CmsContent->setLabel($content['label']);
            $CmsContent->setType($content['type']);
            $page->addContent($CmsContent);
        }
    }

    // create route from template configuration
    public function buildRoute(PersistenceEvent $event)
    {
        $page = $event->getObject();

        if (!$page instanceof CmsPage) {
            return;
        }

        $config = $this->provider->getConfigurationFor($page->getTemplate());

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

        $CmsRoute->setMethods([Request::METHOD_GET]);
        $CmsRoute->setPath('/' . $page->getSlug() . $paramString);
        $CmsRoute->setDefaults(json_encode($defaults));
        $CmsRoute->setRequirements(json_encode($requirements));
        $CmsRoute->setPage($page);

        // link route to current page
        $page->setRoute($CmsRoute);

        // persist route
        $this->em->persist($CmsRoute);
        $this->em->flush();

        $this->warmUpRouteCache();
    }

    // clear cache routing on update
    public function updateRoute(PersistenceEvent $event)
    {
        $page = $event->getObject();

        if (!$page instanceof CmsPage) {
            return;
        }

        $this->warmUpRouteCache();
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
}
