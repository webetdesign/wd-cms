<?php

namespace WebEtDesign\CmsBundle\Routing;

use WebEtDesign\CmsBundle\Entity\CmsRoute;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use WebEtDesign\CmsBundle\Entity\CmsSite;

class ExtraLoader implements LoaderInterface
{
    protected $loaded = false;

    protected $em = null;

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
            /** @var CmsSite $cmsSite */
            $cmsSite = $cmsRoute->getPage()->getSite();
            if ($cmsSite) {
                $langPrefix = !empty($cmsSite->getLocale()) && !$cmsSite->isHostMultilingual() ? '/'. $cmsSite->getLocale() : null;
                $host = !empty($cmsSite->getHost()) ? $cmsSite->getHost() : null;
            }

            // prepare a new route
            $pattern = (isset($langPrefix) && !empty($langPrefix) ? $langPrefix : '') . $cmsRoute->getPath();
            $defaults = [
                '_controller' => !$cmsRoute->getPage()->isActive() ? 'WebEtDesign\CmsBundle\Controller\CmsController::pageDisabled' :
                    $cmsRoute->getController() ?? 'WebEtDesign\CmsBundle\Controller\CmsController::index',
            ];
            if ($cmsRoute->getDefaults()) {
                $defaults = array_merge($defaults, json_decode($cmsRoute->getDefaults(), true));
            }
            if ($cmsRoute->getRequirements()) {
                $requirements = json_decode($cmsRoute->getRequirements(), true);
            }
            $route = new Route($pattern, $defaults, $requirements ?? []);
            if (!empty($host)) {
                $route->setHost($host);
            }
            $route->setMethods($cmsRoute->getMethods());

            $routes->add($cmsRoute->getName(), $route);
        }

        return $routes;
    }

    public function supports($resource, $type = null)
    {
        return 'cms' === $type;
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
