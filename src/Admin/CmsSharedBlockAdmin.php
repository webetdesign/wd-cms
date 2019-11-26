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

final class CmsSharedBlockAdmin extends AbstractAdmin
{
    protected $templateType;
    protected $isMultisite;
    protected $em;

    public function __construct(string $code, string $class, string $baseControllerName, EntityManagerInterface $em, $cmsConfiguration)
    {
        //        $this->templateType = $templateType;
        $this->em          = $em;
        $this->isMultisite = $cmsConfiguration['multisite'];
        parent::__construct($code, $class, $baseControllerName);
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

        $listMapper
            ->add('id')
            ->add('code')
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
            ->add('move', 'move');

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
        $roleAdmin = $this->canManageContent();

        $formMapper
            ->tab('Général')// The tab call is optional
            ->with('', ['box_class' => ''])
            ->add('code', $this->isCurrentRoute('edit') && $roleAdmin ? TextType::class : HiddenType::class, [])
            ->add('label')
            ->add('template', BlockTemplateType::class, ['label' => 'Modèle de block partagé'])
            ->end()// End form group
            ->end()// End tab
        ;

        if ($this->isCurrentRoute('edit') || $this->getRequest()->isXmlHttpRequest()) {
            $formMapper->getFormBuilder()->setMethod('put');

            $contentOptions = [
                'edit'   => 'inline',
                'inline' => 'table',
            ];
            if ($roleAdmin) {
                $contentOptions['sortable'] = 'position';
            }
            $formMapper
                ->tab('Général')// The tab call is optional
                ->with('', ['box_class' => ''])
                ->add('active')
                ->add('public')
                ->end()
                ->end()
                ->tab('Contenus')
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
                    $contentOptions
                )
                ->end()
                ->end();
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
}
