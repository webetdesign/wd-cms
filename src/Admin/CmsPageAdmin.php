<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use App\Entity\Actuality;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use WebEtDesign\CmsBundle\CMS\ConfigurationInterface;
use WebEtDesign\CmsBundle\CMS\Template\PageInterface;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Form\Admin\CmsVarsFormSection;
use WebEtDesign\CmsBundle\Form\Content\AdminCmsBlockCollectionType;
use WebEtDesign\CmsBundle\Form\MoveForm;
use WebEtDesign\CmsBundle\Form\MultilingualType;
use WebEtDesign\CmsBundle\Form\PageTemplateType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\Form\Type\SecurityRolesType;
use WebEtDesign\CmsBundle\Manager\BlockFormThemesManager;
use WebEtDesign\CmsBundle\Registry\BlockRegistry;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;
use WebEtDesign\CmsBundle\Utils\CmsVarsAdminTrait;
use WebEtDesign\CmsBundle\Vars\Compiler;
use WebEtDesign\SeoBundle\Admin\SmoOpenGraphAdminTrait;
use WebEtDesign\SeoBundle\Admin\SmoTwitterAdminTrait;

class CmsPageAdmin extends AbstractAdmin
{
    use SmoTwitterAdminTrait;
    use SmoOpenGraphAdminTrait;
    use CmsVarsAdminTrait;

    protected mixed $multilingual;
    protected mixed $multisite;
    protected mixed $declination;

    protected array  $datagridValues = [];
    protected mixed  $globalVarsEnable;
    protected ?array $cmsConfig;

    public function __construct(
        protected readonly ParameterBagInterface $parameterBag,
        protected readonly EntityManagerInterface $em,
        protected readonly TokenStorageInterface $tokenStorage,
        protected readonly TemplateRegistry $templateRegistry,
        protected readonly BlockRegistry $blockRegistry,
        protected readonly BlockFormThemesManager $blockFormThemesManager,
        protected readonly Compiler $compiler,
        protected readonly ConfigurationInterface $configuration,
    ) {
        $this->cmsConfig    = $this->parameterBag->get('wd_cms.cms');
        $this->multisite    = $this->cmsConfig['multisite'];
        $this->multilingual = $this->cmsConfig['multilingual'];
        $this->declination  = $this->cmsConfig['declination'];

        $this->globalVarsEnable = false; // TODO  $globalVarsDefinition['enable'];

        parent::__construct();
    }


    protected function configureActionButtons(
        array $buttonList,
        string $action,
        ?object $object = null
    ): array {
        $buttons           = parent::configureActionButtons($buttonList, $action, $object);
        $buttons['create'] = ['template' => '@WebEtDesignCms/admin/page/create_button.html.twig'];

        return $buttons;
    }

    protected function configureRoutes(RouteCollection|RouteCollectionInterface $collection): void
    {
        $collection->remove('show');
        $collection->add('move', 'move/{childId}');
        $collection->add('test', 'test');
        $collection->add('list', 'list', ['id' => null], ['id' => '\d*']);
        $collection->add('tree', 'tree', ['id' => null], ['id' => '\d*']);
        $collection->add('create', 'create', ['id' => null], ['id' => '\d*']);
        $collection->add('duplicate', 'duplicate/{childId}', ['id' => null], ['id' => '\d*']);

        parent::configureRoutes($collection);
    }

    //    protected function configureTabMenu(
    //        MenuItemInterface $menu,
    //        string $action,
    //        ?AdminInterface $childAdmin = null
    //    ): void {
    //        $admin   = $this->isChild() ? $this->getParent() : $this;
    //        $subject = $this->isChild() ? $this->getParent()->getSubject() : $this->getSubject();
    //
    //
    //        dump('la');
    //
    //        $id = $this->getRequest()->get('id');
    //
    //        if (!$childAdmin && $action == 'tree') {
    //            $sites = $this->em->getRepository(CmsSite::class)->findAll();
    //            if (sizeof($sites) > 1) {
    //                foreach ($sites as $site) {
    //                    $active = $site->getId() == $this->getRequest()->attributes->get('id');
    //                    $menu->addChild(
    //                        $site->__toString(),
    //                        [
    //                            'uri'        => $admin->generateUrl('tree', ['id' => $site->getId()]),
    //                            'attributes' => ['class' => $active ? 'active' : ""]
    //                        ]
    //                    );
    //                }
    //            }
    //        }
    //
    //        if (!$childAdmin && !in_array($action, ['edit', 'show'])) {
    //            return;
    //        }
    //
    //        if ($this->declination && $subject->getId() != null && $subject->getRoute() && $subject->getRoute()->isDynamic()) {
    //            $menu->addChild(
    //                'Page',
    //                ['uri' => $admin->generateUrl('edit', ['id' => $id])]
    //            );
    //
    //            $menu->addChild(
    //                'Déclinaison',
    //                ['uri' => $admin->generateUrl('cms.admin.cms_page_declination.list', ['id' => $id])]
    //            );
    //        }
    //    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('title')
            ->add('site');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function configureListFields(ListMapper $list): void
    {
        unset($this->getListModes()['mosaic']);

        $roleAdmin = $this->canManageContent();

        if ($roleAdmin) {
            $list->add('id');
        }
        $list->add('title', null, [
            'label' => 'Titre',
        ])
            ->add('route.path', null, [
                'label' => 'Chemin',
            ])
            ->add('active', null, [
                'label' => 'Actif',
            ])
            ->add(
                '_action',
                null,
                [
                    'actions' => [
                        'show'      => [],
                        'edit'      => [],
                        'delete'    => [],
                        'create'    => ['template' => '@WebEtDesignCms/admin/page/list_action_add.html.twig'],
                        'duplicate' => ['template' => '@WebEtDesignCms/admin/page/list_action_duplicate.html.twig']
                    ],
                ]
            );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function configureFormFields(FormMapper $form): void
    {

        $roleAdmin = $this->canManageContent();
        $admin     = $this;
        /** @var CmsPage $object */
        $object = $this->getSubject();

        /** @var PageInterface $template */
        $template = $this->templateRegistry->get($object->getTemplate());

        $site = $object->getSite();

        $form->getFormBuilder()->setAction($this->generateUrl('create',
            ['id' => $site->getId()]));

        $admin->setFormTheme(array_merge($admin->getFormTheme(), [
            '@WebEtDesignCms/admin/nestedTreeMoveAction/wd_cms_move_form.html.twig',
            "@WebEtDesignCms/admin/form/cms_block.html.twig",
            '@WebEtDesignCms/admin/form/dynamic_block.html.twig',
            '@WebEtDesignCms/admin/form/admin_cms_vars_section.html.twig',
        ], $this->blockFormThemesManager->getThemes()));

        //region Général
        $form
            ->tab('cms_page.tab.general')// The tab call is optional
            ->with('', ['box_class' => 'header_none']);

        $form
            ->add('title', null, ['label' => 'cms_page.form.title.label']);
        $form
            ->add('template', PageTemplateType::class, [
                'label'      => 'cms_page.form.template.label',
                'collection' => $site->getTemplateFilter()
            ]);

        $form->add('site', HiddenType::class);
        $form->get('site')->addModelTransformer(new CallbackTransformer(
            fn($value) => $value?->getId(),
            fn($value) => $value ? $this->getEntityManager()->find(CmsSite::class, $value) : null,
        ));

        if ($object->getId() === null) {
            $form
                ->add('position', MoveForm::class, [
                    'data_class' => null,
                    'entity'     => CmsPage::class,
                    'object'     => $object
                ]);
        }

        $form
            ->end() // End form group
            ->end()// End tab
        ;
        //endregion

        if ($this->isCurrentRoute('edit') || $this->getRequest()->isXmlHttpRequest()) {
            $form->getFormBuilder()->setMethod('put');

            //region Général - additional
            $form
                ->tab('cms_page.tab.general')// The tab call is optional
                ->with('', ['box_class' => 'header_none']);

            if ($object->getRoute() !== null) {
                $form
                    ->add('active', null, ['label' => 'cms_page.form.active.label']);
            }

            $form->end();                  // End form group
            $form->end();                  // End tab
            //endregion

            //region SEO
            $form->tab('cms_page.tab.seo');// The tab call is optional
            $this->addFormVarsSection($form, $object, 'seo');
            $form->with('cms_page.tab.general',
                [
                    'class'              => 'col-xs-12 col-md-4',
                    'box_class'          => '',
                    'translation_domain' => 'wd_seo'
                ])
                ->add('seo_title', null, ['label' => 'wd_seo.form.seo_title.label'])
                ->add('seo_description', TextareaType::class,
                    ['label' => 'wd_seo.form.seo_description.label', 'required' => false])
                ->add('breadcrumb', null,
                    ['required' => false, 'label' => 'cms_page.form.seo_breadcrumb.label'],
                    ['translation_domain' => $this->getTranslationDomain()])
                ->end();
            $this->addFormFieldSmoOpenGraph($form);
            $this->addFormFieldSmoTwitter($form);
            $form->end();
            //endregion

            //region Contenus
            if (count($object->getContents()) > 0) {
                $form->tab('cms_page.tab.content');
                $this->addFormVarsSection($form, $object, 'content');
                $form
                    ->with('', [
                        'box_class' => 'header_none',
                        'class'     => $this->globalVarsEnable ? 'col-xs-9' : 'col-xs-12'
                    ])
                    ->add('contents', AdminCmsBlockCollectionType::class, [
                        'templateFactory' => $this->templateRegistry,
                    ])
                    ->end();
                $form
                    ->end();
            }
            //endregion

            if ($object->getRoute() != null) {
                //region Route
                $form->tab('cms_page.tab.route')
                    ->with('', ['box_class' => 'header_none'])
                    ->add('route.name', null, ['label' => 'cms_route.form.name.label'])
                    ->add('route.path', null,
                        [
                            'label' => 'cms_route.form.path.label',
                            'attr'  => ['class' => 'cms_route_path_input']
                        ])
                    ->add(
                        'route.methods',
                        ChoiceType::class,
                        [
                            'label'    => 'cms_route.form.method.label',
                            'choices'  => [
                                Request::METHOD_GET    => Request::METHOD_GET,
                                Request::METHOD_POST   => Request::METHOD_POST,
                                Request::METHOD_DELETE => Request::METHOD_DELETE,
                                Request::METHOD_PUT    => Request::METHOD_PUT,
                                Request::METHOD_PATCH  => Request::METHOD_PATCH,
                            ],
                            'multiple' => true,
                        ]
                    )
                    ->add('route.controller', null, ['label' => 'cms_route.form.controller.label'])
                    ->add('route.defaults', HiddenType::class, [
                        'attr' => [
                            'class' => 'cms_route_default_input'
                        ]
                    ])
                    ->add('route.requirements', HiddenType::class, [
                        'attr' => [
                            'class' => 'cms_route_requirements_input'
                        ]
                    ])
                    ->end()
                    ->end();
                //endregion
            }

            if ($this->cmsConfig['security']['page']['enable']) {
                //region Sécurité
                $form->tab('cms_page.tab.security')
                    ->with('', ['box_class' => ''])
                    ->add('roles', SecurityRolesType::class, [
                        'label'    => false,
                        'expanded' => true,
                        'multiple' => true,
                        'required' => false,
                    ])
                    ->end()
                    ->end();
                //endregion
            }

            if ($this->multilingual) {
                //region MultiLingue
                $form->tab('cms_page.tab.multilingual')
                    ->with('', ['box_class' => 'header_none']);

                if ($object->getRoot()->getSite()) {
                    $form->add('crossSitePages', MultilingualType::class, [
                        'site'           => $object->getRoot()->getSite(),
                        'page'           => $object,
                        'label'          => 'cms_page.form.cross_site_pages.label',
                        'templateFilter' => $site->getTemplateFilter(),
                    ]);

                    $form->getFormBuilder()->get('crossSitePages')->addModelTransformer(new CallbackTransformer(
                        function ($value) {
                            $tab = [];
                            if ($value !== null) {
                                foreach ($value as $item) {
                                    $tab[$item->getRoot()->getSite()->getId()] = $item;
                                }
                            }

                            return $tab;
                        },
                        function ($value) {
                            return array_values(array_filter($value));
                        }
                    ));
                }

                $form->end();
                //endregion
            }
        }
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('title');
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function canManageContent()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        return $user->hasRole('ROLE_ADMIN_CMS');
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query
            ->andWhere(
                $query->expr()->eq($query->getRootAlias() . '.lvl', 0)
            );

        return $query;
    }

}
