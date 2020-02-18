<?php


namespace WebEtDesign\CmsBundle\Collectors;


use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Services\CmsHelper;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class CmsCollector extends DataCollector
{
    protected $data;

    /**
     * @var CmsHelper
     */
    private $cmsHelper;
    /**
     * @var TemplateProvider
     */
    private $templateProvider;

    public function __construct(CmsHelper $cmsHelper, TemplateProvider $templateProvider) {
        $this->cmsHelper = $cmsHelper;
        $this->templateProvider = $templateProvider;
    }

    /**
     * @inheritDoc
     */
    public function collect(Request $request, Response $response)
    {
        /** @var CmsPage $page */
        $page = $this->cmsHelper->getPage($request);
        if ($page) {

            try {
                $config = $this->templateProvider->getConfigurationFor('template');
            } catch (Exception $e) {
                $config = [];
            }

            $this->data = [
                'page' => $page->getTitle(),
                'pageId' => $page->getId(),
                'template' => $page->getTemplate(),
                'twigTemplate' => $config['template'] ?? null
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'cms.collector';
    }

    public function reset()
    {
        $this->data = [];
    }
}
