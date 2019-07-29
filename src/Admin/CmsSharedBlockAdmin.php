<?php

namespace WebEtDesign\CmsBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
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
            ->add('title')
            ->add('active');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('id')
            ->add('title')
            ->add('template')
            ->add('active')
            ->add('_action', null, [
                'actions' => [
                    'show'   => [],
                    'edit'   => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('title')
            ->add('template', BlockTemplateType::class, ['label' => 'Modèle de block'])
            ->add('active');
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('title')
            ->add('template')
            ->add('active');
    }
}
