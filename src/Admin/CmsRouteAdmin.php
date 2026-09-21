<?php

namespace WebEtDesign\CmsBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;

final class CmsRouteAdmin extends AbstractAdmin
{

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('name')
            ->add('methods')
            ->add('path');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $modes = $this->getListModes();
        unset($modes['mosaic']);
        $this->setListModes($modes);

        $listMapper
            ->add('id')
            ->addIdentifier('page')
            ->add('name')
            ->add('methods', null, ['template' => '@WebEtDesignCms/arrayListField.html.twig'])
            ->add('path')
            ->add(ListMapper::NAME_ACTIONS, null, [
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
            ->with('General', ['class' => 'col-md-8',])
            ->add('name')
            ->add('methods', ChoiceType::class, [
                'choices'  => [
                    Request::METHOD_GET    => Request::METHOD_GET,
                    Request::METHOD_POST   => Request::METHOD_POST,
                    Request::METHOD_DELETE => Request::METHOD_DELETE,
                    Request::METHOD_PUT    => Request::METHOD_PUT,
                    Request::METHOD_PATCH  => Request::METHOD_PATCH,
                ],
                'data'    => [
                    Request::METHOD_GET,
                    Request::METHOD_POST,
                    Request::METHOD_DELETE,
                    Request::METHOD_PUT,
                    Request::METHOD_PATCH,
                ],
                'multiple' => true,
            ])
            ->add('path')
            ->add('controller')
            ->end();
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('name')
            ->add('methods', null, ['template' => '@WebEtDesignCms/arrayShowField.html.twig'])
            ->add('path');
    }
}
