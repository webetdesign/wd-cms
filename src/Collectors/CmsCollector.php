<?php


namespace WebEtDesign\CmsBundle\Collectors;


use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;
use WebEtDesign\CmsBundle\Admin\CmsPageAdmin;
use WebEtDesign\CmsBundle\Admin\CmsPageDeclinationAdmin;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Services\CmsHelper;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class CmsCollector extends DataCollector
{
    protected $data;

    private CmsHelper $cmsHelper;
    private TemplateProvider $templateProvider;
    private CmsPageAdmin $cmsPageAdmin;
    private array $cmsConfig;
    private CmsPageDeclinationAdmin $cmsPageDeclinationAdmin;

    public function __construct(
        CmsHelper $cmsHelper,
        TemplateProvider $templateProvider,
        CmsPageAdmin $cmsPageAdmin,
        CmsPageDeclinationAdmin $cmsPageDeclinationAdmin,
        $cmsConfig
    ) {
        $this->cmsHelper               = $cmsHelper;
        $this->templateProvider        = $templateProvider;
        $this->cmsPageAdmin            = $cmsPageAdmin;
        $this->cmsConfig               = $cmsConfig;
        $this->cmsPageDeclinationAdmin = $cmsPageDeclinationAdmin;
    }

    /**
     * @inheritDoc
     */
    public function collect(Request $request, Response $response, Throwable $exception = null)
    {
        /** @var CmsPage $page */
        $page = $this->cmsHelper->getPage($request);
        if ($page) {

            try {
                $config = $this->templateProvider->getConfigurationFor($page->getTemplate());
            } catch (Exception $e) {
                $config = [];
            }

            if ($this->cmsConfig['declination'] && ($declination = $this->getDeclination($page, $request))) {
                $isDeclination      = true;
                $editDeclinationUrl = $this->cmsPageAdmin->generateUrl('cms.admin.cms_page_declination.edit',
                    ['id' => $page->getId(), 'childId' => $declination->getId()]);
                $addDeclinationUrl  = $this->cmsPageAdmin->generateUrl('cms.admin.cms_page_declination.create', ['id' => $page->getId()]);
            }
            $editUrl = $this->cmsPageAdmin->generateUrl('edit', ['id' => $page->getId()]);

            $this->data = [
                'page'               => $page->getTitle(),
                'pageId'             => $page->getId(),
                'template'           => $page->getTemplate(),
                'twigTemplate'       => $config['template'] ?? null,
                'editUrl'            => $editUrl,
                'editDeclinationUrl' => $editDeclinationUrl ?? null,
                'addDeclinationUrl'  => $addDeclinationUrl ?? null,
                'type'               => isset($isDeclination) ? 'Declination' : null
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

    public function getData()
    {
        return $this->data;
    }

    private function getDeclination(CmsPage $page, Request $request)
    {
        $path             = $request->getRequestUri();
        $path             = preg_replace('(\?.*)', '', $path);
        $withoutExtension = $this->cmsConfig['page_extension'] ? preg_replace('/\.([a-z]+)$/', '', $path) : false;

        /** @var CmsPageDeclination $declination */
        foreach ($page->getDeclinations() as $declination) {
            if ($declination->getPath() === $path || $declination->getPath() === $withoutExtension) {
                return $declination;
            }
        }

        return null;
    }
}
