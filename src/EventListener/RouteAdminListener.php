<?php


namespace WebEtDesign\CmsBundle\EventListener;


use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use WebEtDesign\CmsBundle\Entity\CmsRoute;

class RouteAdminListener
{
    /**
     * @var Router
     */
    protected $router;
    /**
     * @var Filesystem
     */
    protected $fs;
    /**
     * @var KernelInterface
     */
    protected $kernel;


    /**
     * RouteAdminListener constructor.
     * @param Router $router
     * @param Filesystem $fs
     * @param KernelInterface $kernel
     */
    public function __construct(Router $router, Filesystem $fs, KernelInterface $kernel) {
        $this->router    = $router;
        $this->fs        = $fs;
        $this->kernel    = $kernel;
    }

    public function postUpdate($event): void
    {
        $route = $event->getObject();

        if (!$route instanceof CmsRoute) {
            return;
        }

        $this->warmUpRouteCache();
    }


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
}
