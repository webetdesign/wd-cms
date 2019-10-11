<?php

namespace WebEtDesign\CmsBundle\Twig;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
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
        $customContents,
        Container $container,
        Environment $twig,
        TemplateProvider $pageProvider,
        TemplateProvider $templateProvider,
        RequestStack $requestStack,
        $declination,
        $globalVarsDefinition
    ) {
        $this->em                  = $entityManager;
        $this->router              = $router;
        $this->customContents      = $customContents;
        $this->container           = $container;
        $this->twig                = $twig;
        $this->pageProvider        = $pageProvider;
        $this->sharedBlockProvider = $templateProvider;
        $this->requestStack        = $requestStack;
        $this->declination         = $declination;

        $this->globalVarsEnable = $globalVarsDefinition['enable'];
        if ($globalVarsDefinition['enable']) {
            $this->globalVars = $this->container->get($globalVarsDefinition['global_service']);
        }
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
            new TwigFunction('cms_render_shared_block', [$this, 'renderSharedBlock'], ['is_safe' => ['html']]),
            new TwigFunction('cms_media', [$this, 'cmsMedia']),
            new TwigFunction('cms_sliders', [$this, 'cmsSliders']),
            new TwigFunction('cms_path', [$this, 'cmsPath']),
            new TwigFunction('cms_render_locale_switch', [$this, 'renderLocaleSwitch'], ['is_safe' => ['html']]),
            new TwigFunction('cms_render_seo_smo_value', [$this, 'renderSeoSmo']),
            new TwigFunction('test', [$this, 'test']),
        ];
    }

    private function getDeclination($page)
    {
        $request = $this->requestStack->getCurrentRequest();
        $path    = $request->getRequestUri();

        /** @var CmsPageDeclination $declination */
        foreach ($page->getDeclinations() as $declination) {
            if ($declination->getPath() == $path) {
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
            if (!$content || !$content->isSet()) {
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

        if ($content->getParentHeritance()) {
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
            return $result;
        }

        return $this->globalVarsEnable ? $this->replaceVars($content->getValue()) : $content->getValue();
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

    public function cmsPath($route, $params = [], $referenceType = UrlGenerator::ABSOLUTE_PATH)
    {
        try {
            return $this->router->generate($route, $params, $referenceType);
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
            if (method_exists($declination, $method)) {
                $value = call_user_func_array([$declination, $method], []);
            } else {
                trigger_error('Call to undefined method ' . get_class($declination) . '::' . $method . '()', E_USER_ERROR);
            }
        } else {
            if (method_exists($object, $method)) {
                $value = call_user_func_array([$object, $method], []);
            } else {
                trigger_error('Call to undefined method ' . get_class($object) . '::' . $method . '()', E_USER_ERROR);
            }
        }

        $value = !empty($value) ? $value : $default;

        if ($this->globalVarsEnable) {
            $value = $this->replaceVars($value);
        }

        return $value;
    }

    public function replaceVars($str)
    {
        $values = $this->globalVars->computeValues($this->globalVars);

        $d = $this->globalVars->getDelimiters();

        foreach ($values as $name => $value) {
            $str = str_replace($d['s'].$name.$d['e'], $value, $str);
        }

        return $str;
    }
}
