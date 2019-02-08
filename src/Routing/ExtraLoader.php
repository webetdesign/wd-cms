<?php

namespace WebEtDesign\CmsBundle\Routing;

use WebEtDesign\CmsBundle\Entity\CmsRoute;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ExtraLoader implements LoaderInterface
{
    private $loaded = false;

    private $em = null;

    /**
     * ExtraLoader constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager) {
        $this->em = $entityManager;
    }


    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "extra" loader twice');
        }

        $routes = new RouteCollection();

        $cmsRoutes = $this->em->getRepository(CmsRoute::class)->findAll();

        /** @var CmsRoute $cmsRoute */
        foreach ($cmsRoutes as $cmsRoute) {
            // prepare a new route
            $pattern = $cmsRoute->getPath();
            $defaults = [
                '_controller' => !$cmsRoute->getPage()->isActive() ? 'App\Controller\CmsController::pageDisabled' :
                    $cmsRoute->getController() ?? 'App\Controller\CmsController::index',
            ];
            $requirements = [
//                'parameter' => '\d+',
            ];
            $route = new Route($pattern, $defaults, $requirements);
            $route->setMethods($cmsRoute->getMethods());

            $routes->add($cmsRoute->getName(), $route);
        }

        return $routes;
    }

    public function supports($resource, $type = null)
    {
        return 'extra' === $type;
    }

    public function getResolver()
    {
        // needed, but can be blank, unless you want to load other resources
        // and if you do, using the Loader base class is easier (see below)
    }

    public function setResolver(LoaderResolverInterface $resolver)
    {
        // same as above
    }
}
