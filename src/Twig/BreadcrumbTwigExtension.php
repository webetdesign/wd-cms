<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use WebEtDesign\CmsBundle\CMS\ConfigurationInterface;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Models\BreadcrumbItem;

class BreadcrumbTwigExtension extends AbstractExtension
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected RequestStack           $requestStact,
        protected RouterInterface        $router,
        protected ConfigurationInterface $configuration,
        protected Environment            $twig
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cms_breadcrumb', [$this, 'breadcrumb'], ['is_safe' => ['html']]),
        ];
    }

    public function breadcrumb(): string
    {
        $page = $this->configuration->getCurrentPage();

        $vars = $this->configuration->getVarsBag();

        $generateUrl = function (string $name, array $params = []): string {
            try {
                return $this->router->generate($name, $params);
            } catch (RouteNotFoundException $exception) {
                return "#404(route:$name)";
            } catch (MissingMandatoryParametersException $exception) {
                return "#500(route:$name|missing_params:" . implode($exception->getMissingParameters()) . ')';
            }
        };

        $pageTitle = function (CmsPage $page) use ($vars): string {
            return $vars->replaceIn($page->getBreadcrumb() ?: $page->getTitle());
        };

        $items = [];
        while ($page != null) {
            if ($page->getRoute() === null) {
                $page = $page->getParent();
                continue;
            }

            $pageService = $this->configuration->getTemplateRegistry()->get($page->getTemplate());

            $item = $pageService->buildBreadcrumbItem($this->em, $this->requestStact->getCurrentRequest(), $page, $generateUrl, $pageTitle($page));

            if ($item) {
                $items[] = $item;
            }

            /** @var CmsPage $page */
            $page = $page->getParent();
        }

        return $this->twig->render('@WebEtDesignCms/partials/_breadcrumb.html.twig', [
            'breadcrumb' => array_reverse($items),
        ]);
    }
}
