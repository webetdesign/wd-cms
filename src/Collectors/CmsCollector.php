<?php
declare(strict_types=1);


namespace WebEtDesign\CmsBundle\Collectors;


use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ReflectionClass;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Profiler\Profile;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Registry\BlockRegistry;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;
use WebEtDesign\CmsBundle\Repository\CmsContentRepository;
use WebEtDesign\CmsBundle\Services\CmsHelper;

class CmsCollector extends AbstractDataCollector implements LateDataCollectorInterface
{
    private CmsHelper              $cmsHelper;
    private array                  $cmsConfig;
    private TemplateRegistry       $pageFactory;
    private AdminInterface         $cmsPageAdmin;
    protected AdminInterface       $cmsPageDeclinationAdmin;
    private Environment            $twig;
    private Profile                $profile;
    private BlockRegistry          $blockRegistry;
    private CmsContentRepository   $cmsContentRepository;
    private RouterInterface        $router;
    private ParameterBagInterface  $parameterBag;
    private RequestStack           $requestStack;
    private EntityManagerInterface $em;

    /**
     * @param TemplateRegistry $templateRegistry
     * @param BlockRegistry $blockRegistry
     * @param CmsHelper $cmsHelper
     * @param Pool $adminPool
     * @param Environment $twig
     * @param Profile $profile
     * @param CmsContentRepository $cmsContentRepository
     * @param RouterInterface $router
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateRegistry $templateRegistry,
        BlockRegistry $blockRegistry,
        CmsHelper $cmsHelper,
        Pool $adminPool,
        Environment $twig,
        Profile $profile,
        CmsContentRepository $cmsContentRepository,
        RouterInterface $router,
        ParameterBagInterface $parameterBag,
        RequestStack $requestStack,
        EntityManagerInterface $em,
    ) {
        $this->pageFactory             = $templateRegistry;
        $this->cmsHelper               = $cmsHelper;
        $this->cmsConfig               = $parameterBag->get('wd_cms.cms');
        $this->cmsPageAdmin            = $adminPool->getAdminByClass(CmsPage::class);
        $this->cmsPageDeclinationAdmin = $adminPool->getAdminByClass(CmsPageDeclination::class);
        $this->twig                    = $twig;
        $this->profile                 = $profile;
        $this->blockRegistry           = $blockRegistry;
        $this->cmsContentRepository    = $cmsContentRepository;
        $this->router                  = $router;
        $this->parameterBag            = $parameterBag;
        $this->requestStack            = $requestStack;
        $this->em                      = $em;
    }

    /**
     * @inheritDoc
     */
    public function collect(Request $request, Response $response, Throwable $exception = null): void
    {

        /** @var CmsPage $page */
        $page = $this->getPage();
        if ($page && $page->getTemplate()) {

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
                $editDeclinationUrl = $this->router->generate('admin_webetdesign_cms_cmssite_cmspage_cmspagedeclination_edit',
                    ['id' => $page->getSite()->getId(), 'childId' => $page->getId(), 'childChildId' => $declination->getId()]);
                $addDeclinationUrl  = $this->router->generate('admin_webetdesign_cms_cmssite_cmspage_cmspagedeclination_create',
                    ['id' => $page->getSite()->getId(), 'childId' => $page->getId()]);
            }
            $editUrl = $this->router->generate('admin_webetdesign_cms_cmssite_cmspage_edit', [
                'id' => $page->getSite()->getId(), 'childId' => $page->getId()
            ]);

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
                $config = $this->data['service']?->getBlock($content->getCode());
                if (!$config) {
                    continue;
                }
                $block       = $this->blockRegistry->get($config);
                $blockRC     = new ReflectionClass($block);
                $transformer = $block->getModelTransformer();

                $value = $transformer->transform($content->getValue(), true);

                $blocks[] = [
                    'config'    => [
                        'code'     => $block->getCode(),
                        'label'    => $block->getLabel(),
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

    public function lateCollect(): void
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

    public function getPage(): ?CmsPage
    {
        if ('admin_webetdesign_cms_cmssite_cmspage_edit' === $this->requestStack->getCurrentRequest()->get('_route')) {
            return $this->em->find(CmsPage::class, $this->requestStack->getCurrentRequest()->get('childId'));
        }

        return $this->cmsHelper->getPage();
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
