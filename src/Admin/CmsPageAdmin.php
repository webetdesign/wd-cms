<?php

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\UserBundle\Form\Type\SecurityRolesType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Form\MoveForm;
use WebEtDesign\CmsBundle\Form\MultilingualType;
use WebEtDesign\CmsBundle\Form\PageTemplateType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\Services\TemplateProvider;
use WebEtDesign\CmsBundle\Utils\GlobalVarsAdminTrait;
use WebEtDesign\CmsBundle\Utils\SmoTwitterAdminTrait;
use WebEtDesign\CmsBundle\Utils\SmoFacebookAdminTrait;

class CmsPageAdmin extends AbstractAdmin
{
    use SmoTwitterAdminTrait;
    use SmoFacebookAdminTrait;
    use GlobalVarsAdminTrait;

    protected $multilingual;
    protected $multisite;
    protected $declination;
    protected $em;

    protected $datagridValues = [];
    protected $globalVarsEnable;
    protected $pageProvider;

    public function __construct(
        string $code,
        string $class,
        string $baseControllerName,
        EntityManager $em,
        $multisite,
        $multilingual,
        $declination,
        $globalVarsDefinition,
        TemplateProvider $pageProvider
    ) {
        $this->multisite        = $multisite;
        $this->multilingual     = $multilingual;
        $this->declination      = $declination;
        $this->em               = $em;
        $this->globalVarsEnable = $globalVarsDefinition['enable'];
        $this->pageProvider     = $pageProvider;

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

        parent::configureRoutes($collection);
    }

    protected function configureSideMenu(MenuItemInterface $menu, $action, AdminInterface $childAdmin = null)
    {
        $admin   = $this->isChild() ? $this->getParent() : $this;
        $subject = $this->isChild() ? $this->getParent()->getSubject() : $this->getSubject();

        $id = $this->getRequest()->get('id');

        if (!$childAdmin && in_array($action, ['tree'])) {
            $sites = $this->em->getRepository(CmsSite::class)->findAll();
            if (sizeof($sites) > 1) {
                foreach ($sites as $site) {
                    $active = $site->getId() == $this->request->attributes->get('id');
                    $menu->addChild(
                        $site->getLabel(),
                        ['uri' => $admin->generateUrl('tree', ['id' => $site->getId()]), 'attributes' => ['class' => $active ? 'active' : ""]]
                    );
                }
            }
        }

        if (!$childAdmin && !in_array($action, ['edit', 'show'])) {
            return;
        }

        if ($this->declination && $subject->getId() != null && $subject->getRoute()->isDynamic()) {
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
                        'create' => ['template' => '@WebEtDesignCms/admin/page/list_action_add.html.twig']
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

        $formMapper->getFormBuilder()->setAction($this->generateUrl('create', ['id' => $site->getId()]));

        $admin->setFormTheme(array_merge($admin->getFormTheme(), [
            '@WebEtDesignCms/form/cms_global_vars_type.html.twig',
            '@WebEtDesignCms/admin/nestedTreeMoveAction/wd_cms_move_form.html.twig'
        ]));

        $container = $this->getConfigurationPool()->getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');

        //region Général
        $formMapper
            ->tab('Général')// The tab call is optional
            ->with('', ['box_class' => '']);

        $formMapper
            ->add('title', null, ['label' => 'Title']);
        if (empty($site->getTemplateFilter())) {
            $formMapper
                ->add('template', PageTemplateType::class, [
                    'label' => 'Modèle de page',
                ]);
        } else {
            $formMapper
                ->add('template', PageTemplateType::class, [
                    'label'   => 'Modèle de page',
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
                ->tab('Général')// The tab call is optional
                ->with('', ['box_class' => ''])
                ->add('active');

            //region Association
            if ($object->getClassAssociation()) {
                $entities = $em->getRepository($object->getClassAssociation())->{$object->getQueryAssociation()}();
                $choices  = [];
                foreach ($entities as $entity) {
                    $choices[$entity->__toString()] = $entity->getId();
                }
                $formMapper->add('association', ChoiceType::class,
                    [
                        'label'   => 'Association',
                        'choices' => $choices
                    ]
                );
            }
            //endregion

            $formMapper->end();// End form group
            $formMapper->end();// End tab
            //endregion

            //region SEO
            $formMapper->tab('SEO');// The tab call is optional
            $this->addGlobalVarsHelp($formMapper, $object, $this->globalVarsEnable);
            $formMapper->with('Général', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
                ->add('seo_title')
                ->add('seo_description')
                ->add('seo_keywords')
                ->end();
            $this->addFormFieldSmoFacebook($formMapper);
            $this->addFormFieldSmoTwitter($formMapper);
            $formMapper->end();
            //endregion

            //region Contenus
            $formMapper->tab('Contenus');
            $this->addGlobalVarsHelp($formMapper, $object, $this->globalVarsEnable);
            $formMapper
                ->with('', ['box_class' => ''])
                ->add(
                    'contents',
                    CollectionType::class,
                    [
                        'label'        => false,
                        'by_reference' => false,
                        'btn_add'      => $roleAdmin ? 'Ajouter' : false,
                        'type_options' => [
                            'delete' => $roleAdmin,
                        ],
                    ],
                    [
                        'edit'     => 'inline',
                        'inline'   => 'table',
                        'sortable' => 'position',
                    ]
                )
                ->end()
                ->end();
            //endregion

            if ($object->getRoute() != null) {
                //region Route
                $formMapper->tab('Route')
                    ->with('', ['box_class' => ''])
                    ->add('route.name', null, ['label' => 'Route name (technique)'])
                    ->add('route.path', null, ['label' => 'Chemin', 'attr' => ['class' => 'cms_route_path_input']])
                    ->add(
                        'route.methods',
                        ChoiceType::class,
                        [
                            'label'    => 'Méthodes',
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
                    ->add('route.controller', null, ['label' => 'Controller (technique)'])
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

            //region Sécurité
            $formMapper->tab('Sécurité')
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

            if ($this->multilingual) {
                //region MultiLingue
                $formMapper->tab('MultiLingue')
                    ->with('', ['box_class' => '']);

                if ($object->getRoot()->getSite()) {
                    $formMapper->add('crossSitePages', MultilingualType::class, [
                        'site'  => $object->getRoot()->getSite(),
                        'page'  => $object,
                        'label' => 'Page associées',
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
