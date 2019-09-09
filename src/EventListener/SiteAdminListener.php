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

class SiteAdminListener
{
    protected $router;
    protected $fs;
    protected $kernel;
    protected $siteClass;

    public function __construct(Router $router, Filesystem $fs, KernelInterface $kernel, $siteClass)
    {
        $this->router    = $router;
        $this->fs        = $fs;
        $this->kernel    = $kernel;
        $this->siteClass = $siteClass;
    }

    public function postUpdate(PersistenceEvent $event)
    {
        $site = $event->getObject();

        if (!$site instanceof $this->siteClass) {
            return;
        }

        $this->warmUpRouteCache();
    }

    public function postPersist(PersistenceEvent $event)
    {
        $site = $event->getObject();

        if (!$site instanceof $this->siteClass) {
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
