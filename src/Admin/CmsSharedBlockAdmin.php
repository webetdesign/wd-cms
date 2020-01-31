<?php

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Form\BlockTemplateType;
use Knp\Menu\ItemInterface as MenuItemInterface;
use WebEtDesign\CmsBundle\Form\CmsContentsType;

final class CmsSharedBlockAdmin extends AbstractAdmin
{
    protected $templateType;
    protected $isMultisite;
    protected $em;
    private   $customFormThemes;

    public function __construct(string $code, string $class, string $baseControllerName, EntityManagerInterface $em, $cmsConfiguration, $customFormThemes)
    {
        //        $this->templateType = $templateType;
        $this->em          = $em;
        $this->isMultisite = $cmsConfiguration['multisite'];
        parent::__construct($code, $class, $baseControllerName);
        $this->customFormThemes = $customFormThemes;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('code')
            ->add('label')
            ->add('active')
            ->add('site')
            ->add('public');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        unset($this->listModes['mosaic']);

        if ($this->canManageContent()){
            $listMapper
                ->add('id')
                ->add('code')
            ;
        }

        $listMapper
            ->add('label')
            ->add('active')
            ->add('public')
            ->add('_action', null, [
                'actions' => [
                    'edit'   => [],
                    'delete' => [],
                ],
            ]);


    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection
            ->add('createRootNode', 'initRoot')
            ->add('move', 'move')
            ->add('create', 'create/{id}', ['id' => null], ['id' => '\d*']);

        $collection->add('list', 'list/{id}', ['id' => null], ['id' => '\d*']);
    }

    protected function configureSideMenu(MenuItemInterface $menu, $action, AdminInterface $childAdmin = null)
    {
        $admin   = $this->isChild() ? $this->getParent() : $this;
        $subject = $this->isChild() ? $this->getParent()->getSubject() : $this->getSubject();

        $id = $this->getRequest()->get('id');

        if (!$childAdmin && in_array($action, ['list'])) {
            $sites = $this->em->getRepository(CmsSite::class)->findAll();
            if (sizeof($sites) > 1) {
                foreach ($sites as $site) {
                    $active = $site->getId() == $id;
                    dump($active);
                    $menu->addChild(
                        $site->getLabel(),
                        ['uri' => $admin->generateUrl('list', ['id' => $site->getId()]), 'attributes' => ['class' => $active ? 'active' : ""]]
                    );
                }
            }
        }
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $admin     = $this;
        $roleAdmin = $this->canManageContent();
        $object    = $this->getSubject();

        $admin->setFormTheme(array_merge($admin->getFormTheme(), [
            '@WebEtDesignCms/form/cms_global_vars_type.html.twig',
            '@WebEtDesignCms/form/cms_contents_type.html.twig',
        ], $this->customFormThemes));

        if ($this->isCurrentRoute('create') && $this->getRequest()->get('id') !== null) {
            $site = $this->em->getRepository('WebEtDesignCmsBundle:CmsSite')->find($this->getRequest()->get('id'));
            $object->setSite($site);
        }

        $formMapper
            ->tab('Général')// The tab call is optional
            ->with('', ['box_class' => ''])
            ->add('site', null, [
                'attr'  => ['style' => 'display:none;'],
                'label' => false
            ])
            ->add('code', $this->isCurrentRoute('edit') && $roleAdmin ? TextType::class : HiddenType::class, [])
            ->add('label')
            ->add('template', BlockTemplateType::class, ['label' => 'Modèle de block partagé'])
            ->end()// End form group
            ->end()// End tab
        ;


        if ($this->isCurrentRoute('edit') || $this->getRequest()->isXmlHttpRequest()) {
            $formMapper->getFormBuilder()->setMethod('put');

            $formMapper
                ->tab('Général')// The tab call is optional
                ->with('', ['box_class' => ''])
                ->add('active')
                ->add('public')
                ->end()
                ->end();
            //region Contenus
            $formMapper->tab('Contenus');
            $formMapper
                ->with('', ['box_class' => 'header_none', 'class' => 'col-xs-12'])
                ->add('contents', CmsContentsType::class, [
                    'label'        => false,
                    'by_reference' => false,
                    'role_admin'   => $roleAdmin,
                ])
                ->end();
            $formMapper
                ->end();
            //endregion
        }
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id');
    }

    protected function canManageContent()
    {
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

        return $user->hasRole('ROLE_ADMIN_CMS');
    }


    /**
     * @inheritDoc
     */
    public function configureActionButtons($action, $object = null)
    {
        $list = [];

        if (\in_array($action, ['tree'], true)
            && $this->hasAccess('create')
            && $this->hasRoute('create')
        ) {
            $list['create'] = [
                // NEXT_MAJOR: Remove this line and use commented line below it instead
                'template' => $this->getTemplate('button_create'),
                //                'template' => $this->getTemplateRegistry()->getTemplate('button_create'),
            ];
        }

        if (\in_array($action, ['show', 'delete', 'acl', 'history'], true)
            && $this->canAccessObject('edit', $object)
            && $this->hasRoute('edit')
        ) {
            $list['edit'] = [
                // NEXT_MAJOR: Remove this line and use commented line below it instead
                'template' => $this->getTemplate('button_edit'),
                //'template' => $this->getTemplateRegistry()->getTemplate('button_edit'),
            ];
        }

        if (\in_array($action, ['show', 'edit', 'acl'], true)
            && $this->canAccessObject('history', $object)
            && $this->hasRoute('history')
        ) {
            $list['history'] = [
                // NEXT_MAJOR: Remove this line and use commented line below it instead
                'template' => $this->getTemplate('button_history'),
                // 'template' => $this->getTemplateRegistry()->getTemplate('button_history'),
            ];
        }

        if (\in_array($action, ['edit', 'history'], true)
            && $this->isAclEnabled()
            && $this->canAccessObject('acl', $object)
            && $this->hasRoute('acl')
        ) {
            $list['acl'] = [
                // NEXT_MAJOR: Remove this line and use commented line below it instead
                'template' => $this->getTemplate('button_acl'),
                // 'template' => $this->getTemplateRegistry()->getTemplate('button_acl'),
            ];
        }

        if (\in_array($action, ['edit', 'history', 'acl'], true)
            && $this->canAccessObject('show', $object)
            && \count($this->getShow()) > 0
            && $this->hasRoute('show')
        ) {
            $list['show'] = [
                // NEXT_MAJOR: Remove this line and use commented line below it instead
                'template' => $this->getTemplate('button_show'),
                // 'template' => $this->getTemplateRegistry()->getTemplate('button_show'),
            ];
        }

        if (\in_array($action, ['show', 'edit', 'delete', 'acl', 'batch'], true)
            && $this->hasAccess('list')
            && $this->hasRoute('list')
        ) {
            $list['list'] = [
                // NEXT_MAJOR: Remove this line and use commented line below it instead
                'template' => $this->getTemplate('button_list'),
                // 'template' => $this->getTemplateRegistry()->getTemplate('button_list'),
            ];
        }

        return $list;
    }
}
