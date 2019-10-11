<?php

namespace WebEtDesign\CmsBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\CmsBundle\Form\BlockTemplateType;

final class CmsSharedBlockAdmin extends AbstractAdmin
{
    protected $templateType;

    public function __construct(string $code, string $class, string $baseControllerName)
    {
//        $this->templateType = $templateType;
        parent::__construct($code, $class, $baseControllerName);
    }


    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('code')
            ->add('label')
            ->add('active')
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
                    [
                        'edit'   => 'inline',
                        'inline' => 'table',
                    ]
                )
                ->end()
                ->end()
            ;

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
