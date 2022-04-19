<?php

namespace WebEtDesign\CmsBundle\Twig;

use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\TwigTest;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Factory\BlockFactory;
use WebEtDesign\CmsBundle\Factory\PageFactory;
use WebEtDesign\CmsBundle\Factory\SharedBlockFactory;
use WebEtDesign\CmsBundle\Form\Transformer\CmsBlockTransformer;
use WebEtDesign\CmsBundle\Services\AbstractCmsGlobalVars;
use WebEtDesign\CmsBundle\Services\WDDeclinationService;
use WebEtDesign\MediaBundle\Entity\Media;
use WebEtDesign\MediaBundle\Services\WDMediaService;

/**
 * @property mixed configCms
 */
class CmsTwigExtension extends AbstractExtension
{
    protected $declination;
    protected $requestStack;
    /** @var AbstractCmsGlobalVars */
    protected                  $globalVars;
    protected                  $globalVarsEnable;
    protected PageFactory      $templateFactory;
    protected                  $pageExtension;
    private SharedBlockFactory $sharedBlockProvider;
    private                    $twig;
    private                    $container;

    private $em;

    protected $router;

    protected                    $customContents;
    private WDMediaService       $mediaService;
    private WDDeclinationService $declinationService;
    private BlockFactory         $blockFactory;

    /**
     * @inheritDoc
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        ContainerInterface $container,
        Environment $twig,
        PageFactory $templateFactory,
        SharedBlockFactory $sharedBlockFactory,
        RequestStack $requestStack,
        ParameterBagInterface $parameterBag,
        WDMediaService $mediaService,
        WDDeclinationService $declinationService,
        BlockFactory $blockFactory
    ) {
        $this->em                  = $entityManager;
        $this->router              = $router;
        $this->container           = $container;
        $this->twig                = $twig;
        $this->templateFactory     = $templateFactory;
        $this->sharedBlockProvider = $sharedBlockFactory;
        $this->requestStack        = $requestStack;

        $this->pageExtension  = $parameterBag->get('wd_cms.cms.page_extension');
        $this->declination    = $parameterBag->get('wd_cms.cms.declination');
        $this->customContents = $parameterBag->get('wd_cms.custom_contents');
        $globalVarsDefinition = $parameterBag->get('wd_cms.vars');
        $this->configCms      = $parameterBag->get('wd_cms.cms');

        $this->globalVarsEnable = $globalVarsDefinition['enable'];
        if ($globalVarsDefinition['enable']) {
            $this->globalVars = $this->container->get($globalVarsDefinition['global_service']);
        }
        $this->mediaService       = $mediaService;
        $this->declinationService = $declinationService;
        $this->blockFactory       = $blockFactory;
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

    private function retrieveContent($object, $content_code): CmsContent
    {
        /** @var CmsContent $content */
        $content = $this->em->getRepository(CmsContent::class)
            ->findOneByObjectAndContentCodeAndType(
                $object,
                $content_code
            );
        return $content;
    }

    private function getContent($object, $content_code)
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

        if ($this->declination && $object instanceof CmsPage) {
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
     * @return string|null
     * @throws Exception
     */
    public function cmsRenderContent($object, $content_code, ?array $context = null)
    {
        [$content, $defaultPage, $defaultLangSite] = $this->getContent($object, $content_code);

        if (!$content) {
            return null;
        }

        $template = $this->templateFactory->get($object->getTemplate());

        $block = $this->blockFactory->get($template->getBlock($content->getCode()));

        $value = $block->render($content->getValue(), $context);

        if ($this->globalVarsEnable) {
            $this->globalVars->replaceVars($content);
        }

        if (!$value && $defaultLangSite && $defaultPage) {
            $content = $this->retrieveContent($defaultPage, $content_code);
            $value   = $this->globalVarsEnable ? $this->globalVars->replaceVars($content->getValue()) : $content->getValue();
        }

        return $value;
    }

    public function getSharedBlock($code, $object = null)
    {
        if ($this->configCms['multilingual']) {
            if (!$object) {
                throw new HttpException('500',
                    'A CmsPage or CmsSharedBlock must be passed as the second parameter of the `cms_render_shared_block` twig function, null given');
            }

            $block = $this->em->getRepository(CmsSharedBlock::class)->findOneBy([
                'code' => $code,
                'site' => $object->getSite()
            ]);
        } else {
            $block = $this->em->getRepository(CmsSharedBlock::class)->findOneBy(['code' => $code]);
        }

        return $this->renderSharedBlock($block);
    }

    public function renderSharedBlock(?CmsSharedBlock $block)
    {
        if (!$block || $block && !$block->isActive()) {
            return null;
        }

        $config = $this->sharedBlockProvider->get($block->getTemplate());

        return $this->twig->render($config->getTemplate(),
            [
                'block' => $block
            ]);
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

    private function getLocalSwithPages(CmsPage $page)
    {
        $request = $this->requestStack->getCurrentRequest();

        $pages = [];

        foreach ($page->getCrossSitePages() as $p) {
            if (!$p->getSite()->isVisible() || $p->getId() === $page->getId()) {
                continue;
            }
            preg_match_all('/\{(\w+)\}/', $p->getRoute()->getPath(), $params);
            $routeParams  = [];
            $paramsConfig = $this->templateFactory->getConfigurationFor($page->getTemplate())['params'];
            foreach ($params[1] as $param) {
                if (isset($paramsConfig[$param]) && isset($paramsConfig[$param]['entity']) && $paramsConfig[$param]['entity'] !== null &&
                    is_subclass_of($paramsConfig[$param]['entity'], TranslatableInterface::class)) {

                    $repoMethod = 'findOneBy' . ucfirst($paramsConfig[$param]['property']);
                    $criterion  = $request->get('_route_params')[$param] ?? null;

                    $object = $this->em->getRepository($paramsConfig[$param]['entity'])
                        ->$repoMethod($criterion, $page->getSite()->getLocale());
                    if ($object) {
                        $getProperty         = 'get' . ucfirst($paramsConfig[$param]['property']);
                        $routeParams[$param] = $object->translate($p->getSite()->getLocale())->$getProperty();
                    }

                } else {
                    if (isset($paramsConfig[$param]) && isset($paramsConfig[$param]['entity']) && $paramsConfig[$param]['entity'] !== null) {
                        $getProperty         = 'get' . ucfirst($paramsConfig[$param]['property']);
                        $routeParams[$param] = $request->get($param)->$getProperty();

                    } else {
                        $routeParams[$param] = $request->get($param);
                    }
                }
            }

            try {
                $path = $this->router->generate($p->getRoute()->getName(), $routeParams);
            } catch (RouteNotFoundException $e) {
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

    public function renderLocaleSwitch(CmsPage $page, $useless = null): ?string
    {
        $pages = $this->getLocalSwithPages($page);

        return $this->twig->render('@WebEtDesignCms/block/cms_locale_switch.html.twig', [
            'page'  => $page,
            'pages' => $pages
        ]);
    }

    public function renderMetaLocalSwitch(CmsPage $page): ?string
    {
        $pages = $this->getLocalSwithPages($page);

        return $this->twig->render('@WebEtDesignCms/block/cms_meta_locale_switch.html.twig', [
            'page'  => $page,
            'pages' => $pages
        ]);
    }

    /**
     * @deprecated use SeoTwigExtension in wd-seo-bundle instead
     */
    public function renderSeoSmo($object, $name, $default = null)
    {
        $method = 'get' . ucfirst($name);

        $value = null;
        if ($object instanceof CmsPage && $this->declination && ($declination = $this->declinationService->getDeclination($object))) {
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

        if ($this->globalVarsEnable) {
            $value = $this->globalVars->replaceVars($value);
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
    public function breadcrumb($page)
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

    public function routeExist($path)
    {
        return (null === $this->router->getRouteCollection()->get($path)) ? false : true;
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
