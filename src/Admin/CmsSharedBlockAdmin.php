<?php

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Factory\SharedBlockFactory;
use WebEtDesign\CmsBundle\Form\BlockTemplateType;
use Knp\Menu\ItemInterface as MenuItemInterface;
use WebEtDesign\CmsBundle\Form\CmsContentsType;
use WebEtDesign\CmsBundle\Form\Content\AdminCmsBlockCollectionType;
use WebEtDesign\CmsBundle\Manager\BlockFormThemesManager;
use WebEtDesign\CmsBundle\Security\Voter\ManageContentVoter;
use function count;
use function in_array;

final class CmsSharedBlockAdmin extends AbstractAdmin
{
    protected ?bool                  $isMultisite;
    protected EntityManagerInterface $em;
    private SharedBlockFactory       $sharedBlockFactory;
    private BlockFormThemesManager   $blockFormThemesManager;

    public function __construct(
        string $code,
        string $class,
        string $baseControllerName,
        EntityManagerInterface $em,
        SharedBlockFactory $sharedBlockFactory,
        ParameterBagInterface $parameterBag,
        BlockFormThemesManager $blockFormThemesManager
    ) {
        parent::__construct($code, $class, $baseControllerName);
        $this->em                     = $em;
        $this->sharedBlockFactory     = $sharedBlockFactory;
        $this->isMultisite            = $parameterBag->get('wd_cms.cms')['multisite'];
        $this->blockFormThemesManager = $blockFormThemesManager;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('site', null, ['show_filter' => false]);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $modes = $this->getListModes();
        unset($modes['mosaic']);
        $this->setListModes($modes);

        if ($this->isGranted(ManageContentVoter::CAN_MANAGE_CONTENT)) {
            $listMapper
                ->add('id')
                ->add('code');
        }

        $listMapper
            ->add('label')
            ->add('active')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'edit'   => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->remove('export')
            ->add('createRootNode', 'initRoot')
            ->add('move', 'move')
            ->add('create', 'create/{id}', ['id' => null], ['id' => '\d*']);

        $collection->add('list', 'list/{id}', ['id' => null], ['id' => '\d*']);
    }

    protected function configureSideMenu(
        MenuItemInterface $menu,
        $action,
        AdminInterface $childAdmin = null
    ) {
        $admin = $this->isChild() ? $this->getParent() : $this;

        $id = $this->getRequest()->get('id');

        if (!$childAdmin && $action == 'list') {
            $sites = $this->em->getRepository(CmsSite::class)->findAll();
            if (sizeof($sites) > 1) {
                foreach ($sites as $site) {
                    $active = $site->getId() == $id;
                    $menu->addChild(
                        $site->__toString(),
                        [
                            'uri'        => $admin->generateUrl('list', ['id' => $site->getId()]),
                            'attributes' => ['class' => $active ? 'active' : ""]
                        ]
                    );
                }
            }
        }
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $admin     = $this;
        $roleAdmin = $this->isGranted(ManageContentVoter::CAN_MANAGE_CONTENT);
        $object    = $this->getSubject();

        $admin->setFormTheme(array_merge($admin->getFormTheme(), [
            '@WebEtDesignCms/form/cms_global_vars_type.html.twig',
            '@WebEtDesignCms/form/cms_contents_type.html.twig',
            '@WebEtDesignCms/customContent/sortable_collection_widget.html.twig',
            '@WebEtDesignCms/customContent/sortable_entity_widget.html.twig',
            '@WebEtDesignCms/admin/form/cms_block.html.twig',
        ], $this->blockFormThemesManager->getThemes()));

        if ($this->isCurrentRoute('create') && $this->getRequest()->get('id') !== null) {
            $site = $this->em->getRepository('WebEtDesignCmsBundle:CmsSite')->find($this->getRequest()->get('id'));
            $object->setSite($site);
        } else {
            $site = $object->getSite();
        }

        // tab Général
        $formMapper
            ->tab('Général')// The tab call is optional
            ->with('', ['box_class' => 'header_none'])
            ->add('site', EntityType::class, [
                'class' => CmsSite::class,
                'attr'  => [
                    'style'               => 'display: none;',
                    'data-sonata-select2' => false
                ],
                'label' => false
            ])
            ->add('code',
                $this->isCurrentRoute('edit') && $roleAdmin ? TextType::class : HiddenType::class,
                [])
            ->add('label')
            ->add('template', BlockTemplateType::class, [
                'label'      => 'Modèle de block partagé',
                'collection' => $site?->getTemplateFilter()
            ]);

        if ($this->isCurrentRoute('edit') || $this->getRequest()->isXmlHttpRequest()) {
            $formMapper->add('active');
        }

        $formMapper
            ->end()// End form group
            ->end()// End tab général
        ;

        if ($this->isCurrentRoute('edit') || $this->getRequest()->isXmlHttpRequest()) {
            $formMapper->getFormBuilder()->setMethod('put');

            //region Contenus
            $formMapper->tab('Contenus');
            $formMapper
                ->with('', ['box_class' => 'header_none', 'class' => 'col-xs-12'])
                ->add('contents', AdminCmsBlockCollectionType::class, [
                    'templateFactory' => $this->sharedBlockFactory,
                ])
                ->end();
            $formMapper->end();
            //endregion
        }
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id');
    }

    /**
     * @inheritDoc
     */
    public function configureActionButtons(
        array $list,
        string $action,
        ?object $object = null
    ): array {
        $list = [];

        if (in_array($action, ['tree', 'list'], true)
            && $this->hasAccess('create')
            && $this->hasRoute('create')
        ) {
            $list['create'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_create'),
            ];
        }

        if (in_array($action, ['show', 'delete', 'acl', 'history'], true)
            && $this->hasAccess('edit', $object)
            && $this->hasRoute('edit')
        ) {
            $list['edit'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_edit'),
            ];
        }

        if (in_array($action, ['show', 'edit', 'acl'], true)
            && $this->hasAccess('history', $object)
            && $this->hasRoute('history')
        ) {
            $list['history'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_history'),
            ];
        }

        if (in_array($action, ['edit', 'history'], true)
            && $this->isAclEnabled()
            && $this->hasAccess('acl', $object)
            && $this->hasRoute('acl')
        ) {
            $list['acl'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_acl'),
            ];
        }

        if (in_array($action, ['edit', 'history', 'acl'], true)
            && $this->hasAccess('show', $object)
            && count($this->getShow()) > 0
            && $this->hasRoute('show')
        ) {
            $list['show'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_show'),
            ];
        }

        if (in_array($action, ['show', 'edit', 'delete', 'acl', 'batch'], true)
            && $this->hasAccess('list')
            && $this->hasRoute('list')
        ) {
            $list['list'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_list'),
            ];
        }

        return $list;
    }
}
