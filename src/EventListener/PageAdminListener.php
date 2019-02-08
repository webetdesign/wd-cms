<?php

namespace App\EventListener;

use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Services\PageProvider;
use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\Event\PersistenceEvent;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class PageAdminListener
{
    private $provider;
    private $em;
    private $router;
    private $fs;
    private $kernel;

    public function __construct(PageProvider $provider, EntityManager $em, Router $router, Filesystem $fs, KernelInterface $kernel)
    {
        $this->provider = $provider;
        $this->em       = $em;
        $this->router   = $router;
        $this->fs       = $fs;
        $this->kernel   = $kernel;
    }

    // create page form template configuration
    public function buildPage(PersistenceEvent $event)
    {
        $page = $event->getObject();

        if (!$page instanceof CmsPage) {
            return;
        }

        $config = $this->provider->getConfigurationFor($page->getTemplate());

        // hydrate content
        foreach ($config['contents'] as $content) {
            $CmsContent = new CmsContent();
            $CmsContent->setCode($content['label']);
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

        // hydrate route
        $CmsRoute = new CmsRoute();
        $CmsRoute->setName(sprintf('cms_route_%s', $page->getId()));

        if ($config['controller'] && $config['action']) {
            $CmsRoute->setController(sprintf('%s::%s', $config['controller'], $config['action']));
        }

        $CmsRoute->setMethods([Request::METHOD_GET]);
        $CmsRoute->setPath('/'.$page->getSlug());
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

        foreach (array('matcher_cache_class', 'generator_cache_class') as $option) {
            $className = $this->router->getOption($option);
            $cacheFile = $cacheDir.DIRECTORY_SEPARATOR.$className.'.php';
            $this->fs->remove($cacheFile);
        }

        $this->router->warmUp($cacheDir);
    }
}
