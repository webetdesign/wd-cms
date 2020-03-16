<?php

namespace WebEtDesign\CmsBundle\Twig;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\TwigTest;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsContentHasSharedBlock;
use WebEtDesign\CmsBundle\Entity\CmsGlobalVarsDelimiterEnum;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsContentTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Services\AbstractCmsGlobalVars;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class CmsTwigExtension extends AbstractExtension
{
    protected $declination;
    protected $requestStack;
    /** @var AbstractCmsGlobalVars */
    protected $globalVars;
    protected $globalVarsEnable;
    protected $pageProvider;
    protected $pageExtension;
    private   $sharedBlockProvider;
    private   $twig;
    private   $container;

    private $em;

    protected $router;

    protected $customContents;

    /**
     * @inheritDoc
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        Container $container,
        Environment $twig,
        TemplateProvider $pageProvider,
        TemplateProvider $templateProvider,
        RequestStack $requestStack,
        ParameterBagInterface $parameterBag
    )
    {
        $this->em                  = $entityManager;
        $this->router              = $router;
        $this->container           = $container;
        $this->twig                = $twig;
        $this->pageProvider        = $pageProvider;
        $this->sharedBlockProvider = $templateProvider;
        $this->requestStack        = $requestStack;

        $this->pageExtension  = $parameterBag->get('wd_cms.cms.page_extension');
        $this->declination    = $parameterBag->get('wd_cms.cms.declination');
        $this->customContents = $parameterBag->get('wd_cms.custom_contents');
        $globalVarsDefinition = $parameterBag->get('wd_cms.vars');

        $this->globalVarsEnable = $globalVarsDefinition['enable'];
        if ($globalVarsDefinition['enable']) {
            $this->globalVars = $this->container->get($globalVarsDefinition['global_service']);
        }
    }

    public function getTests()
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
            new TwigFunction('cms_render_content', [$this, 'cmsRenderContent'], ['is_safe' => ['html']]),
            new TwigFunction('cms_render_shared_block', [$this, 'getSharedBlock'], ['is_safe' => ['html']]),
            new TwigFunction('cms_media', [$this, 'cmsMedia']),
            new TwigFunction('cms_sliders', [$this, 'cmsSliders']),
            new TwigFunction('cms_path', [$this, 'cmsPath']),
            new TwigFunction('cms_render_locale_switch', [$this, 'renderLocaleSwitch'], ['is_safe' => ['html']]),
            new TwigFunction('cms_render_seo_smo_value', [$this, 'renderSeoSmo']),
            new TwigFunction('cms_breadcrumb', [$this, 'breadcrumb']),
            new TwigFunction('route_exist', [$this, 'routeExist']),
            new TwigFunction('choice_label', [$this, 'choiceLabel']),
        ];
    }

    public function isInstanceOf($object, $class)
    {
        return $object instanceof $class;
    }

    private function getDeclination($page)
    {
        $request          = $this->requestStack->getCurrentRequest();
        $path             = $request->getRequestUri();
        $path             = preg_replace('(\?.*)', '', $path);
        $withoutExtension = $this->pageExtension ? preg_replace('/\.([a-z]+)$/', '', $path) : false;

        /** @var CmsPageDeclination $declination */
        foreach ($page->getDeclinations() as $declination) {
            if (
                preg_match('/^' . preg_quote($declination->getPath(), '/') . '/', $path) ||
                preg_match('/^' . preg_quote($declination->getPath(), '/') . '/', $withoutExtension)
            ) {
                return $declination;
                break;
            }
        }

        return null;
    }

    private function getContent($object, $content_code)
    {
        /** @var CmsContent $content */
        $content = $this->em->getRepository(CmsContent::class)
            ->findOneByObjectAndContentCodeAndType(
                $object,
                $content_code,
                array_merge([
                    CmsContentTypeEnum::TEXT,
                    CmsContentTypeEnum::TEXTAREA,
                    CmsContentTypeEnum::WYSYWYG,
                    CmsContentTypeEnum::SHARED_BLOCK,
                    CmsContentTypeEnum::SHARED_BLOCK_COLLECTION,
                    CmsContentTypeEnum::MEDIA,
                    CmsContentTypeEnum::IMAGE,
                    CmsContentTypeEnum::CHECKBOX,
                ], array_keys($this->customContents))
            );

        return $content;
    }

    /**
     * @param CmsPage|CmsSharedBlock $object
     * @param $content_code
     * @return string|null
     * @throws Exception
     */
    public function cmsRenderContent($object, $content_code)
    {
        if ($this->declination && $object instanceof CmsPage) {
            $content = null;
            if ($this->getDeclination($object)) {
                $content = $this->getContent($this->getDeclination($object), $content_code);
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
                    $content = $this->getContent($object, $content_code);
                }
            } else {
                $content = $this->getContent($object, $content_code);
            }
        } else {
            $content = $this->getContent($object, $content_code);
        }

        if (!$content) {
            return null;
        }

        if (!$content->isActive()) {
            return null;
        }

        while ($content->getParentHeritance() && $content->getPage()->getParent()){
            $content = $this->em->getRepository(CmsContent::class)->findParent($content);
        }

        if (in_array($content->getType(), array_keys($this->customContents))) {
            $contentService = $this->container->get($this->customContents[$content->getType()]['service']);

            return $contentService->render($content);
        }

        if ($content->getType() === CmsContentTypeEnum::SHARED_BLOCK) {
            $block = $this->em->getRepository(CmsSharedBlock::class)->find((int)$content->getValue());
            if (!$block) {
                return null;
            }

            return $this->renderSharedBlock($block);
        }

        if ($content->getType() === CmsContentTypeEnum::SHARED_BLOCK_COLLECTION) {
            $result = '';
            /** @var CmsContentHasSharedBlock $item */
            foreach ($content->getSharedBlockList() as $item) {
                $result .= $this->renderSharedBlock($item->getSharedBlock());
            }

                dump($result);
            return $result;
        }

        if ($content->getType() === CmsContentTypeEnum::CHECKBOX) {
            return filter_var($content->getValue(), FILTER_VALIDATE_BOOLEAN);
        }

        return $this->globalVarsEnable ? $this->globalVars->replaceVars($content->getValue()) : $content->getValue();
    }

    public function getSharedBlock($code)
    {
        $block = $this->em->getRepository(CmsSharedBlock::class)->findOneBy(['code' => $code]);

        return $this->renderSharedBlock($block);
    }

    public function renderSharedBlock(CmsSharedBlock $block)
    {
        if (!$block) {
            return null;
        }

        return $this->twig->render($this->sharedBlockProvider->getConfigurationFor($block->getTemplate())['template'], [
            'block' => $block
        ]);
    }

    public function cmsMedia($object, $content_code)
    {
        if ($this->declination && $object instanceof CmsPageDeclination) {
            $content = $this->getContent($object, $content_code);
            if (!$content->isSet()) {
                $content = $this->getContent($object->getPage(), $content_code);
            }
        } else {
            $content = $this->getContent($object, $content_code);
        }

        if (!$content) {
            return null;
        }

        if (!$content->isActive()) {
            return null;
        }

        return $content->getMedia();
    }

    public function cmsSliders(CmsPage $page, $content_code)
    {
        /** @var CmsContent $content */
        $content = $this->em->getRepository(CmsContent::class)
            ->findOneByObjectAndContentCodeAndType(
                $page,
                $content_code,
                [
                    CmsContentTypeEnum::SLIDER,
                ]
            );
        if (!$content) {
            if (getenv('APP_ENV') != 'dev') {
                return null;
            } else {
                $message = sprintf(
                    'No content sliders found with the code "%s" in page "%s" (#%s)',
                    $content_code,
                    $page->getTitle(),
                    $page->getId()
                );
                throw new Exception($message);
            }
        }

        return $content->getSliders();
    }

    public function cmsPath($route, $params = [], $absoluteUrl = false)
    {
        try {
            return $this->router->generate($route, $params, $absoluteUrl ? UrlGenerator::ABSOLUTE_URL : UrlGenerator::ABSOLUTE_PATH);
        } catch (RouteNotFoundException $e) {
            return '#404(route:' . $route . ')';
        }
    }

    public function renderLocaleSwitch(CmsPage $page, Request $request)
    {
        $pages = [];

        foreach ($page->getCrossSitePages() as $page) {
            preg_match_all('/\{(\w+)\}/', $page->getRoute()->getPath(), $params);
            $routeParams = [];
            foreach ($params[1] as $param) {
                $routeParams[$param] = $request->get($param);
            }

            $pages[] = [
                'path' => $this->router->generate($page->getRoute()->getName(), $routeParams),
                'icon' => $page->getRoot()->getSite()->getFlagIcon(),
            ];
        }

        return $this->twig->render('@WebEtDesignCms/block/cms_locale_switch.html.twig', [
            'pages' => $pages
        ]);
    }

    public function renderSeoSmo($object, $name, $default = null)
    {
        $method = 'get' . ucfirst($name);

        $value = null;
        if ($object instanceof CmsPage && $this->declination && ($declination = $this->getDeclination($object))) {
            $value = $this->getSeoSmoValue($declination, $method);
        } else {
            if ($object instanceof CmsPage) {
                $value = $this->getSeoSmoValueFallbackParentPage($object, $method);
            } else {
                $this->getSeoSmoValue($object, $method);
            }
        }

        $value = !empty($value) ? $value : $default;

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
            trigger_error('Call to undefined method ' . get_class($object) . '::' . $method . '()', E_USER_ERROR);
        }
    }

    /**
     * @param CmsPage $page
     */
    public function breadcrumb($page)
    {
        $items = [];
        while ($page != null) {
            if (!$page->getRoute()->isDynamic()) {
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
