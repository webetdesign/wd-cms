<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Routing;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

class ExtraLoader implements LoaderInterface
{
    protected bool $loaded = false;

    protected ?EntityManager $em = null;

    protected ?LoaderResolverInterface $resolver;

    protected TemplateRegistry $templateRegistry;

    /**
     * ExtraLoader constructor.
     * @param EntityManager $entityManager
     * @param TemplateRegistry $templateRegistry
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TemplateRegistry $templateRegistry,
    ) {
        $this->em               = $entityManager;
        $this->templateRegistry = $templateRegistry;
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
                    && !empty($cmsSite->getLocalhost())) {
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

            $route = new Route($pattern, $defaults, $requirements ?? []);
            if (!empty($host)) {
                $route->setHost($host);
            }
            $route->setMethods($cmsRoute->getMethods());

            $priority = 0;
            try {
                $pageConfig     = $this->templateRegistry->get($cmsRoute->getPage()->getTemplate());
                $routeDefnition = $pageConfig->getRoute();
                if ($routeDefnition !== null) {
                    $priority = $routeDefnition->getPriority();
                }
            } catch (Exception $e) {

            }


            preg_match_all('/\{(\w+)\}/', $cmsRoute->getPath(), $matches);
            $routes [] = [
                'nbParams' => count($matches[1]),
                'name'     => $cmsRoute->getName(),
                'route'    => $route,
                'priority' => $priority
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
            $routeCollection->add($route['name'], $route['route'], $route['priority']);
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

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        $this->resolver = $resolver;
    }
}
