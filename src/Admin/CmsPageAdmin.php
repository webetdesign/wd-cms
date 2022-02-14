<?php

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Builder\FormContractorInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Form\CmsContentsType;
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
use WebEtDesign\CmsBundle\Services\TemplateProvider;
use WebEtDesign\CmsBundle\Utils\GlobalVarsAdminTrait;
use WebEtDesign\SeoBundle\Admin\SmoOpenGraphAdminTrait;
use WebEtDesign\SeoBundle\Admin\SmoTwitterAdminTrait;

class CmsPageAdmin extends AbstractAdmin
{
    use SmoTwitterAdminTrait;
    use SmoOpenGraphAdminTrait;
    use GlobalVarsAdminTrait;

    protected mixed $multilingual;
    protected mixed $multisite;
    protected mixed $declination;

    protected array $datagridValues = [];
    protected mixed $globalVarsEnable;
    protected FormContractorInterface $customFormContractor;

    public function __construct(
        string $code,
        string $class,
        string $baseControllerName,
        private EntityManager $em,
        private ContainerInterface $container,
        private $cmsConfig,
        $globalVarsDefinition,
        private TemplateProvider $pageProvider,
        private $customFormThemes
    ) {
        $this->multisite        = $cmsConfig['multisite'];
        $this->multilingual     = $cmsConfig['multilingual'];
        $this->declination      = $cmsConfig['declination'];
        $this->globalVarsEnable = $globalVarsDefinition['enable'];

        parent::__construct($code, $class, $baseControllerName);
    }

    protected function configureActionButtons(array $buttonList, string $action, ?object $object = null): array
    {
        $buttons           = parent::configureActionButtons($buttonList, $action, $object);
        $buttons['create'] = ['template' => '@WebEtDesignCms/admin/page/create_button.html.twig'];

        return $buttons;
    }

    protected function configureRoutes(RouteCollection|RouteCollectionInterface $collection): void
    {
        $collection->add('move', 'move/{id}');
        $collection->add('test', 'test');
        $collection->add('list', 'list/{id}', ['id' => null], ['id' => '\d*']);
        $collection->add('tree', 'tree/{id}', ['id' => null], ['id' => '\d*']);
        $collection->add('create', 'create/{id}', ['id' => null], ['id' => '\d*']);
        $collection->add('duplicate', 'duplicate/{id}', ['id' => null], ['id' => '\d*']);

        parent::configureRoutes($collection);
    }

    protected function configureSideMenu(
        MenuItemInterface $menu,
        $action,
        AdminInterface $childAdmin = null
    ) {
        $admin   = $this->isChild() ? $this->getParent() : $this;
        $subject = $this->isChild() ? $this->getParent()->getSubject() : $this->getSubject();

        $id = $this->getRequest()->get('id');

        if (!$childAdmin && $action == 'tree') {
            $sites = $this->em->getRepository(CmsSite::class)->findAll();
            if (sizeof($sites) > 1) {
                foreach ($sites as $site) {
                    $active = $site->getId() == $this->getRequest()->attributes->get('id');
                    $menu->addChild(
                        $site->__toString(),
                        [
                            'uri'        => $admin->generateUrl('tree', ['id' => $site->getId()]),
                            'attributes' => ['class' => $active ? 'active' : ""]
                        ]
                    );
                }
            }
        }

        if (!$childAdmin && !in_array($action, ['edit', 'show'])) {
            return;
        }

        if ($this->declination && $subject->getId() != null && $subject->getRoute() && $subject->getRoute()->isDynamic()) {
            $menu->addChild(
                'Page',
                ['uri' => $admin->generateUrl('edit', ['id' => $id])]
            );

            $menu->addChild(
                'Déclinaison',
                ['uri' => $admin->generateUrl('cms.admin.cms_page_declination.list', ['id' => $id])]
            );
        }
    }

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
                        'show'   => [],
                        'edit'   => [],
                        'delete' => [],
                        'create' => ['template' => '@WebEtDesignCms/admin/page/list_action_add.html.twig'],
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

        $site = $object->getSite();

        $form->getFormBuilder()->setAction($this->generateUrl('create',
            ['id' => $site->getId()]));

        $admin->setFormTheme(array_merge($admin->getFormTheme(), [
            '@WebEtDesignCms/form/cms_global_vars_type.html.twig',
            '@WebEtDesignCms/form/cms_contents_type.html.twig',
            '@WebEtDesignCms/admin/nestedTreeMoveAction/wd_cms_move_form.html.twig',
            '@WebEtDesignCms/customContent/sortable_collection_widget.html.twig',
            '@WebEtDesignCms/customContent/sortable_entity_widget.html.twig',
        ], $this->customFormThemes));

        //region Général
        $form
            ->tab('cms_page.tab.general')// The tab call is optional
            ->with('', ['box_class' => '']);

        $form
            ->add('title', null, ['label' => 'cms_page.form.title.label']);
        if (empty($site->getTemplateFilter())) {
            $form
                ->add('template', PageTemplateType::class, [
                    'label' => 'cms_page.form.template.label',
                ]);
        } else {
            $form
                ->add('template', PageTemplateType::class, [
                    'label'   => 'cms_page.form.template.label',
                    'choices' => $this->pageProvider->getTemplateList($site->getTemplateFilter())
                ]);
        }
        $form
            ->add('site', EntityType::class, [
                'class' => CmsSite::class,
                'data'  => $site,
                'attr'  => [
                    'style' => 'display: none '
                ],
                'label' => false,
            ]);

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
                ->with('', ['box_class' => ''])
                ->add('active', null, ['label' => 'cms_page.form.active.label']);

            $form->end();// End form group
            $form->end();// End tab
            //endregion

            //region SEO
            $form->tab('cms_page.tab.seo');// The tab call is optional
            $this->addGlobalVarsHelp($form, $object, $this->globalVarsEnable);
            $form->with('cms_page.tab.general', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
                ->add('seo_title', null, ['label' => 'wd_seo.form.seo_title.label'])
                ->add('seo_description', TextareaType::class, ['label' => 'wd_seo.form.seo_description.label', 'required' => false])
                ->add('breadcrumb', null, ['required' => false, 'label' => 'cms_page.form.seo_breadcrumb.label'])
                ->end();
            $this->addFormFieldSmoOpenGraph($form);
            $this->addFormFieldSmoTwitter($form);
            $form->end();
            //endregion

            //region Contenus
            $form->tab('cms_page.tab.content');
            $form
                ->with('', [
                    'box_class' => 'header_none',
                    'class'     => $this->globalVarsEnable ? 'col-xs-9' : 'col-xs-12'
                ])
                ->add('contents', CmsContentsType::class, [
                    'label'        => false,
                    'by_reference' => false,
                    'role_admin'   => $roleAdmin,
                ])
                ->end();
            $this->addGlobalVarsHelp($form, $object, $this->globalVarsEnable, true);
            $form
                ->end();
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
                    ->with('', ['box_class' => '']);

                if ($object->getRoot()->getSite()) {
                    $form->add('crossSitePages', MultilingualType::class, [
                        'site'  => $object->getRoot()->getSite(),
                        'page'  => $object,
                        'label' => 'cms_page.form.cross_site_pages.label',
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
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

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
