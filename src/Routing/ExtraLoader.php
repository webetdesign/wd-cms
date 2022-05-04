<?php

namespace WebEtDesign\CmsBundle\Routing;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ExtraLoader implements LoaderInterface
{
    protected bool $loaded = false;

    protected ?EntityManager $em = null;

    protected ?ContainerBagInterface $parameterBag = null;
    private ?array                   $cmsConfig;

    protected ?LoaderResolverInterface $resolver;

    /**
     * ExtraLoader constructor.
     * @param EntityManager $entityManager
     * @param ContainerBagInterface $parameterBag
     */
    public function __construct(
        EntityManager $entityManager,
        ContainerBagInterface $parameterBag,
        $cmsConfig
    ) {
        $this->em           = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->cmsConfig    = $cmsConfig;
    }

    public function load($resource, $type = null): RouteCollection
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

        $routes = [];

        /** @var CmsRoute $cmsRoute */
        foreach ($cmsRoutes as $cmsRoute) {
            if ($cmsRoute->getPage() == null || $cmsRoute->getPage()->getRoot() == null || !$cmsRoute->getPage()->getActive()) {
                continue;
            }
            //            /** @var CmsSite $cmsSite */
            $cmsSite = $cmsRoute->getPage()->getRoot()->getSite();
            if ($cmsSite) {
                $langPrefix = !empty($cmsSite->getLocale()) && !$cmsSite->isHostMultilingual() ? '/' . $cmsSite->getLocale() : null;
                $host       = !empty($cmsSite->getHost()) ? $cmsSite->getHost() : null;
                if (isset($_ENV['MULTISITE_LOCALHOST'])
                    && filter_var($_ENV['MULTISITE_LOCALHOST'], FILTER_VALIDATE_BOOLEAN)
                    && !empty($cmsSite->getLocalhost()))
                {
                    $host = $cmsSite->getLocalhost();
                }
            }

            // prepare a new route
            $pattern  = (isset($langPrefix) && !empty($langPrefix) ? $langPrefix : '') . $cmsRoute->getPath();
            $defaults = [
                '_controller' => !$cmsRoute->getPage()->isActive() ? 'WebEtDesign\CmsBundle\Controller\CmsController::pageDisabled' :
                    $cmsRoute->getController() ?? 'WebEtDesign\CmsBundle\Controller\CmsController::index',
            ];


            if ($locale = $cmsSite->getLocale()) {
                $defaults['_locale'] = $locale;
            }

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

            if ($this->parameterBag->get('wd_cms.cms.page_extension')) {
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

            preg_match_all('/\{(\w+)\}/', $cmsRoute->getPath(), $matches);
            $routes [] = [
                'nbParams' => count($matches[1]),
                'name'     => $cmsRoute->getName(),
                'route'    => $route
            ];
        }

        uasort($routes, function ($a, $b) {
            if ($a['nbParams'] == $b['nbParams']) {
                return 0;
            }
            return $a['nbParams'] < $b['nbParams'] ? -1 : 1;
        });

        $routeCollection = new RouteCollection();
        foreach ($routes as $route) {
            $routeCollection->add($route['name'], $route['route']);
        }


        //        if ($this->cmsConfig['multisite']) {
        //            $sitemap = new Route('/sitemap.xml', [
        //                '_controller' => 'WebEtDesign\CmsBundle\Controller\SitemapController'
        //            ]);
        //            $routeCollection->add('sitemap', $sitemap);
        //        }

        return $routeCollection;
    }

    public function supports($resource, $type = null): bool
    {
        return 'cms' === $type;
    }

    public function getResolver(): ?LoaderResolverInterface
    {
        return $this->resolver;
    }

    public function setResolver(LoaderResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }
}
