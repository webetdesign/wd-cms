<?php


namespace WebEtDesign\CmsBundle\Collectors;


use Exception;
use ReflectionClass;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Profiler\Profile;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Factory\BlockFactory;
use WebEtDesign\CmsBundle\Factory\PageFactory;
use WebEtDesign\CmsBundle\Repository\CmsContentRepository;
use WebEtDesign\CmsBundle\Services\CmsHelper;

class CmsCollector extends AbstractDataCollector implements LateDataCollectorInterface
{
    protected $data;

    private CmsHelper            $cmsHelper;
    private array                $cmsConfig;
    private PageFactory          $pageFactory;
    private AdminInterface       $cmsPageAdmin;
    protected AdminInterface     $cmsPageDeclinationAdmin;
    private Environment          $twig;
    private Profile              $profile;
    private BlockFactory         $blockFactory;
    private CmsContentRepository $cmsContentRepository;

    public function __construct(
        PageFactory $pageFactory,
        BlockFactory $blockFactory,
        CmsHelper $cmsHelper,
        Pool $adminPool,
        Environment $twig,
        Profile $profile,
        CmsContentRepository $cmsContentRepository,
        $cmsConfig
    ) {
        $this->pageFactory             = $pageFactory;
        $this->cmsHelper               = $cmsHelper;
        $this->cmsConfig               = $cmsConfig;
        $this->cmsPageAdmin            = $adminPool->getAdminByClass(CmsPage::class);
        $this->cmsPageDeclinationAdmin = $adminPool->getAdminByClass(CmsPageDeclination::class);
        $this->twig                    = $twig;
        $this->profile                 = $profile;
        $this->blockFactory            = $blockFactory;
        $this->cmsContentRepository    = $cmsContentRepository;
    }

    /**
     * @inheritDoc
     */
    public function collect(Request $request, Response $response, Throwable $exception = null)
    {
        /** @var CmsPage $page */
        $page = $this->cmsHelper->getPage();
        if ($page) {

            try {
                $service = $this->pageFactory->get($page->getTemplate());
                $SRC     = new ReflectionClass($service);
            } catch (Exception $e) {
                $service = null;
                $SRC     = null;
            }


            if ($this->cmsConfig['declination'] && ($declination = $this->getDeclination($page,
                    $request))) {
                $isDeclination      = true;
                $editDeclinationUrl = $this->cmsPageAdmin->generateUrl('cms.admin.cms_page_declination.edit',
                    ['id' => $page->getId(), 'childId' => $declination->getId()]);
                $addDeclinationUrl  = $this->cmsPageAdmin->generateUrl('cms.admin.cms_page_declination.create',
                    ['id' => $page->getId()]);
            }
            $editUrl = $this->cmsPageAdmin->generateUrl('edit', ['id' => $page->getId()]);

            $this->data = [
                'page'               => $page,
                'service'            => $service,
                'serviceRC'          => [
                    'className' => $SRC?->getName(),
                    'fileName'  => $SRC?->getFileName(),
                ],
                'editUrl'            => $editUrl,
                'editDeclinationUrl' => $editDeclinationUrl ?? null,
                'addDeclinationUrl'  => $addDeclinationUrl ?? null,
                'type'               => isset($isDeclination) ? 'Declination' : null
            ];


            $blocks = [];
            foreach ($this->cmsContentRepository->findBy(['page' => $this->data['page']]) as $content) {
                $block       = $this->blockFactory->get($this->data['service']->getBlock($content->getCode()));
                $blockRC     = new ReflectionClass($block);
                $transformer = $block->getModelTransformer();

                $value = $transformer->transform($content->getValue(), true);

                $blocks[] = [
                    'config' => [
                        'code' => $block->getCode(),
                        'label' => $block->getLabel(),
                        'template' => $block->getTemplate(),
                        'settings' => $block->getSettings(),
                    ],
                    'serviceRC' => [
                        'className' => $blockRC->getName(),
                        'fileName'  => $blockRC->getFileName(),
                    ],
                    'value'     => $value,
                ];
            }
            $this->data['content'] = $this->cmsContentRepository->findBy(['page' => $this->data['page']]);
            $this->data['blocks']  = $blocks;
        }
    }

    public function lateCollect()
    {
        $this->data['template_paths'] = [];

        $templateFinder = function (Profile $profile) use (&$templateFinder) {
            if ($profile->isTemplate()) {
                try {
                    $template = $this->twig->load($name = $profile->getName());
                } catch (LoaderError $e) {
                    $template = null;
                }

                if (null !== $template && '' !== $path = $template->getSourceContext()->getPath()) {
                    $this->data['template_paths'][$name] = $path;
                }
            }

            foreach ($profile as $p) {
                $templateFinder($p);
            }
        };
        $templateFinder($this->profile);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'cms.collector';
    }

    public function getData()
    {
        return $this->data;
    }

    public function getTemplatePaths()
    {
        return $this->data['template_paths'];
    }

    public function getBlocks()
    {
        return $this->data['blocks'];
    }

    private function getDeclination(CmsPage $page, Request $request): ?CmsPageDeclination
    {
        $path             = $request->getRequestUri();
        $path             = preg_replace('(\?.*)', '', $path);
        $withoutExtension = $this->cmsConfig['page_extension'] ? preg_replace('/\.([a-z]+)$/', '',
            $path) : false;

        /** @var CmsPageDeclination $declination */
        foreach ($page->getDeclinations() as $declination) {
            if ($declination->getPath() === $path || $declination->getPath() === $withoutExtension) {
                return $declination;
            }
        }

        return null;
    }
}
