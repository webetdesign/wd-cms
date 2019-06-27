<?php

namespace WebEtDesign\CmsBundle\Twig;

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

class CmsTwigExtension extends AbstractExtension
{
    private $em;

    protected $router;

    protected $contentTypeOption;

    /**
     * @inheritDoc
     */
    public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, $contentTypeOption)
    {
        $this->em                = $entityManager;
        $this->router            = $router;
        $this->contentTypeOption = $contentTypeOption;
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
            new TwigFunction('cms_media', [$this, 'cmsMedia']),
            new TwigFunction('cms_sliders', [$this, 'cmsSliders']),
            new TwigFunction('cms_path', [$this, 'cmsPath']),
            new TwigFunction('cms_project_collection', [$this, 'cmsProjectCollection']),
        ];
    }

    public function cmsRenderContent(CmsPage $page, $content_code)
    {
        /** @var CmsContent $content */
        $content = $this->em->getRepository(CmsContent::class)
            ->findOneByPageAndContentCodeAndType(
                $page,
                $content_code,
                [
                    CmsContentTypeEnum::TEXT,
                    CmsContentTypeEnum::TEXTAREA,
                    CmsContentTypeEnum::WYSYWYG,
                ]
            );

        if (!$content) {
            if (getenv('APP_ENV') != 'dev') {
                return null;
            } else {
                $message = sprintf(
                    'Content not found with the code "%s" in page "%s" (#%s)',
                    $content_code,
                    $page->getTitle(),
                    $page->getId()
                );
                throw new Exception($message);
            }
        }

        return $content->getValue();
    }

    public function cmsMedia(CmsPage $page, $content_code)
    {
        /** @var CmsContent $content */
        $content = $this->em->getRepository(CmsContent::class)
            ->findOneByPageAndContentCodeAndType(
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
            ->findOneByPageAndContentCodeAndType(
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

    public function cmsProjectCollection(CmsPage $page, $content_code)
    {
        $content = $this->em->getRepository(CmsContent::class)
            ->findOneByPageAndContentCodeAndType(
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

        $objects = $this->em->getRepository($this->contentTypeOption[CmsContentTypeEnum::PROJECT_COLLECTION]['class'])->findBy(['id' => json_decode($content->getValue())]);

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
