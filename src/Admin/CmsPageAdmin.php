<?php

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Builder\FormContractorInterface;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\Form\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsContentTypeEnum;
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
use WebEtDesign\CmsBundle\Utils\SmoOpenGraphAdminTrait;
use WebEtDesign\CmsBundle\Utils\SmoTwitterAdminTrait;

class CmsPageAdmin extends AbstractAdmin
{
    use SmoTwitterAdminTrait;
    use SmoOpenGraphAdminTrait;
    use GlobalVarsAdminTrait;

    protected $multilingual;
    protected $multisite;
    protected $declination;
    protected $em;

    protected $datagridValues = [];
    protected $globalVarsEnable;
    protected $pageProvider;
    protected $customFormThemes;
    /** @var FormContractorInterface */
    protected $customFormContractor;
    private   $cmsConfig;

    public function __construct(
        string $code,
        string $class,
        string $baseControllerName,
        EntityManager $em,
        $cmsConfig,
        $globalVarsDefinition,
        TemplateProvider $pageProvider,
        $customFormThemes
    ) {
        $this->cmsConfig        = $cmsConfig;
        $this->multisite        = $cmsConfig['multisite'];
        $this->multilingual     = $cmsConfig['multilingual'];
        $this->declination      = $cmsConfig['declination'];
        $this->em               = $em;
        $this->globalVarsEnable = $globalVarsDefinition['enable'];
        $this->pageProvider     = $pageProvider;
        $this->customFormThemes = $customFormThemes;

        parent::__construct($code, $class, $baseControllerName);
    }

    /**
     * @inheritDoc
     */
    public function getActionButtons($action, $object = null)
    {
        $buttons           = parent::getActionButtons($action, $object);
        $buttons['create'] = ['template' => '@WebEtDesignCms/admin/page/create_button.html.twig'];

        return $buttons;
    }

    protected function configureRoutes(RouteCollection $collection)
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

        if (!$childAdmin && in_array($action, ['tree'])) {
            $sites = $this->em->getRepository(CmsSite::class)->findAll();
            if (sizeof($sites) > 1) {
                foreach ($sites as $site) {
                    $active = $site->getId() == $this->request->attributes->get('id');
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

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('title')
            ->add('site');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);

        $roleAdmin = $this->canManageContent();

        if ($roleAdmin) {
            $listMapper->add('id');
        }
        $listMapper->add('title', null, [
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

    protected function configureFormFields(FormMapper $formMapper)
    {
        $roleAdmin = $this->canManageContent();
        $admin     = $this;
        /** @var CmsPage $object */
        $object = $this->getSubject();

        $site = $object->getSite();

        $formMapper->getFormBuilder()->setAction($this->generateUrl('create',
            ['id' => $site->getId()]));

        $admin->setFormTheme(array_merge($admin->getFormTheme(), [
            '@WebEtDesignCms/form/cms_global_vars_type.html.twig',
            '@WebEtDesignCms/form/cms_contents_type.html.twig',
            '@WebEtDesignCms/admin/nestedTreeMoveAction/wd_cms_move_form.html.twig',
            '@WebEtDesignCms/customContent/sortable_collection_widget.html.twig',
            '@WebEtDesignCms/customContent/sortable_entity_widget.html.twig',
        ], $this->customFormThemes));

        $container = $this->getConfigurationPool()->getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');

        //region Général
        $formMapper
            ->tab('cms_page.tab.general')// The tab call is optional
            ->with('', ['box_class' => '']);

        $formMapper
            ->add('title', null, ['label' => 'cms_page.form.title.label']);
        if (empty($site->getTemplateFilter())) {
            $formMapper
                ->add('template', PageTemplateType::class, [
                    'label' => 'cms_page.form.template.label',
                ]);
        } else {
            $formMapper
                ->add('template', PageTemplateType::class, [
                    'label'   => 'cms_page.form.template.label',
                    'choices' => $this->pageProvider->getTemplateList($site->getTemplateFilter())
                ]);
        }
        $formMapper
            ->add('site', EntityType::class, [
                'class' => CmsSite::class,
                'data'  => $site,
                'attr'  => [
                    'style' => 'display: none '
                ],
                'label' => false,
            ]);

        if ($object->getId() === null) {
            $formMapper
                ->add('position', MoveForm::class, [
                    'data_class' => null,
                    'entity'     => CmsPage::class,
                    'object'     => $object
                ]);
        }

        $formMapper
            ->end() // End form group
            ->end()// End tab
        ;
        //endregion

        if ($this->isCurrentRoute('edit') || $this->getRequest()->isXmlHttpRequest()) {
            $formMapper->getFormBuilder()->setMethod('put');

            //region Général - additional
            $formMapper
                ->tab('cms_page.tab.general')// The tab call is optional
                ->with('', ['box_class' => ''])
                ->add('active', null, ['label' => 'cms_page.form.active.label']);

            $formMapper->end();// End form group
            $formMapper->end();// End tab
            //endregion

            //region SEO
            $formMapper->tab('cms_page.tab.seo');// The tab call is optional
            $this->addGlobalVarsHelp($formMapper, $object, $this->globalVarsEnable);
            $formMapper->with('cms_page.tab.general', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
                ->add('seo_title', null, ['label' => 'cms_page.form.seo_title.label'])
                ->add('seo_description', TextareaType::class, ['label' => 'cms_page.form.seo_description.label', 'required' => false])
                ->add('seo_keywords', null, ['label' => 'cms_page.form.seo_keywords.label'])
                ->add('seo_breadcrumb', null, ['label' => 'cms_page.form.seo_breadcrumb.label'])
                ->end();
            $this->addFormFieldSmoOpenGraph($formMapper);
            $this->addFormFieldSmoTwitter($formMapper);
            $formMapper->end();
            //endregion

            //region Contenus
            $formMapper->tab('cms_page.tab.content');
            $formMapper
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
            $this->addGlobalVarsHelp($formMapper, $object, $this->globalVarsEnable, true);
            $formMapper
                ->end();
            //endregion

            if ($object->getRoute() != null) {
                //region Route
                $formMapper->tab('cms_page.tab.route')
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
                $formMapper->tab('cms_page.tab.security')
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
                $formMapper->tab('cms_page.tab.multilingual')
                    ->with('', ['box_class' => '']);

                if ($object->getRoot()->getSite()) {
                    $formMapper->add('crossSitePages', MultilingualType::class, [
                        'site'  => $object->getRoot()->getSite(),
                        'page'  => $object,
                        'label' => 'cms_page.form.cross_site_pages.label',
                    ]);

                    $formMapper->getFormBuilder()->get('crossSitePages')->addModelTransformer(new CallbackTransformer(
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

                $formMapper->end();
                //endregion
            }
        }
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('name')
            ->add('title');
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    protected function canManageContent()
    {
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

        return $user->hasRole('ROLE_ADMIN_CMS');
    }

    public function createQuery($context = 'list')
    {
        /** @var QueryBuilder $query */
        $query = parent::createQuery($context);
        $alias = $query->getRootAlias();

        $query
            ->andWhere(
                $query->expr()->eq($alias . '.lvl', 0)
            );

        return $query;
    }
}
