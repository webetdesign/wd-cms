<?php

namespace WebEtDesign\CmsBundle\Routing;

use Exception;
use PDOException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ExtraLoader implements LoaderInterface
{
    protected $loaded = false;

    protected $em = null;

    protected $parameterBag = null;

    /**
     * ExtraLoader constructor.
     * @param EntityManager $entityManager
     * @param ContainerBagInterface $parameterBag
     */
    public function __construct(EntityManager $entityManager, ContainerBagInterface $parameterBag)
    {
        $this->em           = $entityManager;
        $this->parameterBag = $parameterBag;
    }

    public function load($resource, $type = null)
    {
        try {
            $con = $this->em->getConnection();
            $con->connect();
            $cmsRoutes = $this->em->getRepository(CmsRoute::class)->findAll();
        } catch (Exception $exception) {
            return new RouteCollection();
        }

        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "extra" loader twice');
        }

        $routes = new RouteCollection();

        /** @var CmsRoute $cmsRoute */
        foreach ($cmsRoutes as $cmsRoute) {
            if ($cmsRoute->getPage() == null || $cmsRoute->getPage()->getRoot() == null) {
                continue;
            }
//            /** @var CmsSite $cmsSite */
            $cmsSite = $cmsRoute->getPage()->getRoot()->getSite();
            if ($cmsSite) {
                $langPrefix = !empty($cmsSite->getLocale()) && !$cmsSite->isHostMultilingual() ? '/' . $cmsSite->getLocale() : null;
                $host       = !empty($cmsSite->getHost()) ? $cmsSite->getHost() : null;
            }

            // prepare a new route
            $pattern  = (isset($langPrefix) && !empty($langPrefix) ? $langPrefix : '') . $cmsRoute->getPath();
            $defaults = [
                '_controller' => !$cmsRoute->getPage()->isActive() ? 'WebEtDesign\CmsBundle\Controller\CmsController::pageDisabled' :
                    $cmsRoute->getController() ?? 'WebEtDesign\CmsBundle\Controller\CmsController::index',
            ];

            if ($cmsRoute->getDefaults()) {
                $defaults = array_merge($defaults, json_decode($cmsRoute->getDefaults(), true));
            }

            if ($cmsRoute->getRequirements()) {
                $requirements = json_decode($cmsRoute->getRequirements(), true);
                foreach ($requirements as $key => $requirement) {
                    if (empty($requirement)) {
                        unset($requirements[$key]);
                    }
                }
            }

            if ($this->parameterBag->get('wd_cms.cms.extension')) {
                if ($pattern !== '/' && strpos($pattern, '.{extension}') === false) {
                    $pattern               .= '.{extension}';
                    $defaults['extension'] = '';
                }
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
