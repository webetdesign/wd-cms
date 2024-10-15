<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Twig;

use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\TwigTest;
use WebEtDesign\CmsBundle\CMS\Template\PageInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Registry\BlockRegistry;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;
use WebEtDesign\CmsBundle\Services\CmsHelper;
use WebEtDesign\CmsBundle\Services\WDDeclinationService;
use WebEtDesign\MediaBundle\Entity\Media;

class CmsTwigExtension extends AbstractExtension
{
    protected bool         $useDeclination = false;
    protected RequestStack $requestStack;

    protected TemplateRegistry $templateRegistry;
    private Environment        $twig;

    private EntityManagerInterface $em;

    protected RouterInterface $router;

    private WDDeclinationService $declinationService;
    private BlockRegistry        $blockRegistry;
    private CmsHelper            $cmsHelper;
    private array                $configCms;

    public function __construct(
        EntityManagerInterface $entityManager,
        RouterInterface        $router,
        Environment            $twig,
        TemplateRegistry       $templateRegistry,
        RequestStack           $requestStack,
        ParameterBagInterface  $parameterBag,
        WDDeclinationService   $declinationService,
        BlockRegistry          $blockRegistry,
        CmsHelper              $cmsHelper
    )
    {
        $this->em               = $entityManager;
        $this->router           = $router;
        $this->twig             = $twig;
        $this->templateRegistry = $templateRegistry;
        $this->requestStack     = $requestStack;

        $this->useDeclination = $parameterBag->get('wd_cms.cms.declination');
        $this->configCms      = $parameterBag->get('wd_cms.cms');

        $this->declinationService = $declinationService;
        $this->blockRegistry      = $blockRegistry;
        $this->cmsHelper          = $cmsHelper;
    }

    public function getTests(): array
    {
        return [
            new TwigTest('instanceOf', [$this, 'isInstanceOf'])
        ];
    }

    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/2.x/advanced.html#automatic-escaping
            //            new TwigFilter('filter_name', [$this, 'doSomething']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cms_render_content', [$this, 'cmsRenderContent'],
                ['is_safe' => ['html']]),
            new TwigFunction('cms_render_shared_block', [$this, 'getSharedBlock'],
                ['is_safe' => ['html']]),
            new TwigFunction('cms_path', [$this, 'cmsPath']),
            new TwigFunction('cms_render_locale_switch', [$this, 'renderLocaleSwitch'],
                ['is_safe' => ['html']]),
            new TwigFunction('cms_render_meta_locale_switch', [$this, 'renderMetaLocalSwitch'],
                ['is_safe' => ['html']]),
            new TwigFunction('cms_render_seo_smo_value', [$this, 'renderSeoSmo']),
            new TwigFunction('cms_breadcrumb', [$this, 'breadcrumb']),
            new TwigFunction('route_exist', [$this, 'routeExist']),
            new TwigFunction('choice_label', [$this, 'choiceLabel']),
        ];
    }

    public function isInstanceOf($object, $class): bool
    {
        return $object instanceof $class;
    }

    private function retrieveContent($object, $content_code): ?CmsContent
    {
        /** @var CmsContent $content */
        $content = $this->em->getRepository(CmsContent::class)
            ->findOneByObjectAndContentCodeAndType(
                $object,
                $content_code
            );

        return $content;
    }

    private function getContent($object, $content_code): ?array
    {
        $defaultLangSite = $this->em->getRepository(CmsSite::class)->findOneBy(['default' => true]);
        $defaultPage     = null;
        if ($defaultLangSite && $this->configCms['multilingual'] && $object instanceof CmsPage) {
            $defaultPages = $object->getCrossSitePages()->filter(function (CmsPage $crossPage) use (
                $defaultLangSite
            ) {
                return $crossPage->getSite() === $defaultLangSite;
            });

            if ($defaultPages->count()) {
                $defaultPage = $defaultPages->first();
            }
        }

        if ($this->useDeclination && $object instanceof CmsPage) {
            $content = null;
            if ($declination = $this->declinationService->getDeclination($object)) {
                $content = $this->retrieveContent($declination, $content_code);
                if (!$content->getValue() && $defaultLangSite && $this->configCms['multilingual']) {
                    $technicName        = preg_replace('/^' . $declination->getLocale() . '_(.*)/',
                        $defaultLangSite->getLocale() . '_$1', $declination->getTechnicName());
                    $declinationDefault = $this->em->getRepository(CmsPageDeclination::class)->findOneBy(['technic_name' => $technicName]);
                    if ($declinationDefault) {
                        $content = $this->retrieveContent($declinationDefault, $content_code);
                    }
                }
            }
            if ($content) {
                $isSet = false;
                if (in_array($content->getType(), array_keys($this->customContents))) {
                    $contentService = $this->container->get($this->customContents[$content->getType()]['service']);
                    if (method_exists($contentService, 'isSet')) {
                        $isSet = $contentService->isSet($content);
                    } else {
                        throw new Exception('You must defined an isSet method in ' . get_class($contentService) . ' for work with the declinations system');
                    }
                } else {
                    $isSet = $content->isSet();
                }
                if (!$isSet) {
                    $content = $this->retrieveContent($object, $content_code);
                }
            } else {
                $content = $this->retrieveContent($object, $content_code);
            }
        } else {
            $content = $this->retrieveContent($object, $content_code);
        }

        if (!$content || !$content->isActive()) {
            return null;
        }

        while ($content !== null && $content->getParentHeritance() && $content->getPage()->getParent()) {
            $content = $this->em->getRepository(CmsContent::class)->findParent($content);
        }

        if (!$content || !$content->isActive()) {
            return null;
        }

        return [$content, $defaultPage, $defaultLangSite];
    }

    /**
     * @param CmsPage|CmsPageDeclination|CmsSharedBlock $object
     * @param $content_code
     * @param array|null $context
     * @return string|null
     * @throws Exception
     */
    public function cmsRenderContent($object, $content_code, ?array $context = null): null|array|string|object|bool
    {
        [$content, $defaultPage, $defaultLangSite] = $this->getContent($object, $content_code);

        if (!$content) {
            return null;
        }

        $template = $this->templateRegistry->get($object->getTemplate());

        $block = $this->blockRegistry->get($template->getBlock($content->getCode()));

        return $block->render($content->getValue(), $context);
    }

    public function getSharedBlock($code, $context = []): ?string
    {
        if ($this->configCms['multilingual']) {
            $page  = $this->cmsHelper->getPage();
            $block = $this->em->getRepository(CmsSharedBlock::class)->findOneBy([
                'code' => $code,
                'site' => $page->getSite()
            ]);
        } else {
            $block = $this->em->getRepository(CmsSharedBlock::class)->findOneBy(['code' => $code]);
        }

        return $this->renderSharedBlock($block, $context);
    }

    public function renderSharedBlock(?CmsSharedBlock $block, $context = []): ?string
    {
        if (!$block || $block && !$block->isActive()) {
            return null;
        }

        $config = $this->templateRegistry->get($block->getTemplate());

        return $this->twig->render($config->getTemplate(),
            array_merge(['block' => $block, 'page' => $this->cmsHelper->getPage()], $context));
    }

    public function cmsPath($route, $params = [], $absoluteUrl = false, CmsPage $page = null)
    {
        if ($this->configCms['multilingual'] && $page !== null) {
            $prefix = $page->getSite()->getLocale() . '_';
        }

        try {
            return $this->router->generate(($prefix ?? null) . $route, $params,
                $absoluteUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH);
        } catch (RouteNotFoundException $e) {
            return '#404(route:' . $route . ')';
        }
    }

    private function generatePageUrl(CmsPage $page)
    {
        $request = $this->requestStack->getCurrentRequest();

        preg_match_all('/\{(\w+)\}/', $page->getRoute()->getPath(), $params);

        /** @var PageInterface $pageConfig */
        $pageConfig  = $this->templateRegistry->get($page->getTemplate());
        $routeConfig = $pageConfig->getRoute();

        $routeParams = [];
        foreach ($routeConfig->getAttributes() as $attribute) {
            if ($attribute->getEntityClass() !== null && is_subclass_of($attribute->getEntityClass(),
                    TranslatableInterface::class)) {
                $repoMethod = 'findOneBy' . ucfirst($attribute->getEntityProperty() ?: 'id');
                $criterion  = $request->get('_route_params')[$attribute->getName()] ?? null;

                $object = $this->em->getRepository($attribute->getEntityClass())->$repoMethod($criterion, $page->getSite()->getLocale());

                if ($object) {
                    $getProperty                        = 'get' . ucfirst($attribute->getEntityProperty() ?: 'id');
                    $routeParams[$attribute->getName()] = $object->translate($page->getSite()->getLocale())->$getProperty();
                }
            } else {
                if ($attribute->getEntityClass() !== null) {
                    $getProperty                        = 'get' . ucfirst($attribute->getEntityProperty() ?: 'id');
                    $routeParams[$attribute->getName()] = $request->get($attribute->getName())->$getProperty();
                } else {
                    $routeParams[$attribute->getName()] = $request->get($attribute->getName());
                }
            }
        }

        try {
            $path = $this->router->generate($page->getRoute()->getName(), $routeParams);
        } catch (RouteNotFoundException $e) {
            $path = null;
        }

        return $path;
    }

    private function getLocalSwitchPages(CmsPage $page): array
    {
        $request = $this->requestStack->getCurrentRequest();

        $pages = [];

        foreach ($page->getCrossSitePages() as $p) {
            if (!$p->getSite()->isVisible() || $p->getId() === $page->getId()) {
                continue;
            }

            $path = $this->generatePageUrl($p);

            if ($path === null) {
                continue;
            }

            $pages[] = [
                'path'   => $path,
                'code'   => $p->getSite()->getLocale(),
                'icon'   => $p->getSite()->getFlagIcon(),
                'locale' => $p->getSite()->getLocale(),
            ];
        }

        return $pages;
    }

    public function renderLocaleSwitch(?CmsPage $page = null, $useless = null): ?string
    {
        if ($page === null) {
            return null;
        }

        $pages = $this->getLocalSwitchPages($page);

        return $this->twig->render('@WebEtDesignCms/block/cms_locale_switch.html.twig', [
            'page'  => $page,
            'pages' => $pages
        ]);
    }

    public function renderMetaLocalSwitch(CmsPage $page): ?string
    {
        $pages = $this->getLocalSwitchPages($page);

        $path = $this->generatePageUrl($page);

        return $this->twig->render('@WebEtDesignCms/block/cms_meta_locale_switch.html.twig', [
            'canonical'  => [
                'path'   => $path,
                'code'   => $page->getSite()->getLocale(),
                'icon'   => $page->getSite()->getFlagIcon(),
                'locale' => $page->getSite()->getLocale(),
            ],
            'alternates' => $pages
        ]);
    }

    /**
     * @deprecated use SeoTwigExtension in wd-seo-bundle instead
     */
    public function renderSeoSmo($object, $name, $default = null)
    {
        $method = 'get' . ucfirst($name);

        $value = null;
        if ($object instanceof CmsPage && $this->useDeclination && ($declination = $this->declinationService->getDeclination($object))) {
            $value = $this->getSeoSmoValue($declination, $method);
            if (empty($value)) {
                $value = $this->getSeoSmoValueFallbackParentPage($object, $method);
            }
        } else {
            if ($object instanceof CmsPage) {
                $value = $this->getSeoSmoValueFallbackParentPage($object, $method);
            } else {
                $value = $this->getSeoSmoValue($object, $method);
            }
        }

        $value = !empty($value) ? $value : $default;

        if ($value instanceof Media) {
            return $value;
        }

        return $value;
    }

    private function getSeoSmoValueFallbackParentPage(CmsPage $object, $method)
    {
        $value = $this->getSeoSmoValue($object, $method);
        if (empty($value) && $object->getParent() !== null) {
            return $this->getSeoSmoValueFallbackParentPage($object->getParent(), $method);
        }

        return $value;
    }

    private function getSeoSmoValue($object, $method)
    {
        if ($object == null) {
            return null;
        }

        if (method_exists($object, $method)) {
            return call_user_func_array([$object, $method], []);
        } else {
            //            trigger_error('Call to undefined method ' . get_class($object) . '::' . $method . '()',
            //                E_USER_ERROR);
            return null;
        }
    }

    /**
     * @param CmsPage $page
     */
    public function breadcrumb($page): array
    {
        $items = [];
        while ($page != null) {
            if ($page->getRoute() && !$page->getRoute()->isDynamic()) {
                $items[] = [
                    'title' => $page->getTitle(),
                    'link'  => $this->router->generate($page->getRoute()->getName())
                ];
            }
            /** @var CmsPage $page */
            $page = $page->getParent();
        }

        return array_reverse($items);
    }

    public function routeExist($path): bool
    {
        return !(null === $this->router->getRouteCollection()->get($path));
    }

    public function choiceLabel($choices, $value)
    {
        /** @var ChoiceView $choice */
        foreach ($choices as $choice) {
            if ($choice->value == $value) {
                return $choice->label;
            }
        }

        return null;
    }
}
