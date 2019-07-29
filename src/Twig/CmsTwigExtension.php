<?php

namespace WebEtDesign\CmsBundle\Twig;

use Symfony\Component\DependencyInjection\Container;
use Twig\Environment;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsContentTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class CmsTwigExtension extends AbstractExtension
{
    private $sharedBlockProvider;
    private $twig;
    private $container;

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
        TemplateProvider $templateProvider
    ) {
        $this->em                  = $entityManager;
        $this->router              = $router;
        $this->customContents      = $customContents;
        $this->container           = $container;
        $this->twig                = $twig;
        $this->sharedBlockProvider = $templateProvider;
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
            new TwigFunction('cms_render_shared_block', [$this, 'cmsSharedBlock'], ['is_safe' => ['html']]),
            new TwigFunction('cms_media', [$this, 'cmsMedia']),
            new TwigFunction('cms_sliders', [$this, 'cmsSliders']),
            new TwigFunction('cms_path', [$this, 'cmsPath']),
            new TwigFunction('cms_project_collection', [$this, 'cmsProjectCollection']),
        ];
    }

    /**
     * @param CmsPage|CmsSharedBlock $object
     * @param $content_code
     * @return string|null
     * @throws Exception
     */
    public function cmsRenderContent($object, $content_code)
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
                ], array_keys($this->customContents))
            );

        if (!$content) {
            if (getenv('APP_ENV') != 'dev') {
                return null;
            } else {
                if ($object instanceof CmsPage) {
                    $message = sprintf('Content not found with the code "%s" in page "%s" (#%s)', $content_code, $object->getTitle(), $object->getId());
                }
                if ($object instanceof CmsSharedBlock) {
                    $message = sprintf('Content not found with the code "%s" in sharedBlock "%s" (#%s)', $content_code, $object->getLabel(), $object->getId());
                }
                throw new Exception($message);
            }
        }


        if (!$content->isActive()) {
            return null;
        }

        if (in_array($content->getType(), array_keys($this->customContents))) {
            $contentService = $this->container->get($this->customContents[$content->getType()]['service']);
            return $contentService->render($content);
        }

        return $content->getValue();
    }

    public function cmsMedia(CmsPage $page, $content_code)
    {
        /** @var CmsContent $content */
        $content = $this->em->getRepository(CmsContent::class)
            ->findOneByObjectAndContentCodeAndType(
                $page,
                $content_code,
                [
                    CmsContentTypeEnum::MEDIA,
                ]
            );
        if (!$content) {
            if (getenv('APP_ENV') != 'dev') {
                return null;
            } else {
                $message = sprintf(
                    'No content media found with the code "%s" in page "%s" (#%s)',
                    $content_code,
                    $page->getTitle(),
                    $page->getId()
                );
                throw new Exception($message);
            }
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

    public function cmsSharedBlock(CmsPage $page, $content_code)
    {
        /** @var CmsContent $content */
        $content = $this->em->getRepository(CmsContent::class)
            ->findOneByObjectAndContentCodeAndType(
                $page,
                $content_code,
                [CmsContentTypeEnum::SHARED_BLOCK]
            );

        if (!$content) {
            dump('toto');
            if (getenv('APP_ENV') != 'dev') {
                return null;
            } else {
                $message = sprintf('Content not found with the code "%s" in page "%s" (#%s)', $content_code, $page->getTitle(), $page->getId());
                throw new Exception($message);
            }
        }

        if (!$content->isActive()) {
            return null;
        }

        $block = $this->em->getRepository(CmsSharedBlock::class)->find((int)$content->getValue());
        if (!$block) {
            return null;
        }

        return $this->twig->render($this->sharedBlockProvider->getConfigurationFor($block->getTemplate())['template'], [
            'block' => $block
        ]);
    }

    public function cmsProjectCollection(CmsPage $page, $content_code)
    {
        $content = $this->em->getRepository(CmsContent::class)
            ->findOneByObjectAndContentCodeAndType(
                $page,
                $content_code,
                [
                    CmsContentTypeEnum::PROJECT_COLLECTION,
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

        $objects = $this->em->getRepository($this->customContents[CmsContentTypeEnum::PROJECT_COLLECTION]['class'])->findBy(['id' => json_decode($content->getValue())]);

        shuffle($objects);
        return $objects;
    }

    public function cmsPath($route, $params = [], $referenceType = UrlGenerator::ABSOLUTE_PATH)
    {
        try {
            return $this->router->generate($route, $params, $referenceType);
        } catch (RouteNotFoundException $e) {
            return '#404(route:' . $route . ')';
        }
    }
}
