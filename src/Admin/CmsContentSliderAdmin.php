<?php
/**
 * Created by PhpStorm.
 * User: jvaldena
 * Date: 22/02/2019
 * Time: 15:47
 */

namespace WebEtDesign\CmsBundle\Admin;


use App\Entity\Media;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class CmsContentSliderAdmin extends AbstractAdmin
{

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('title')
            ->add('_action', null, [
                'actions' => [
                    'show'   => [],
                    'edit'   => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $admin = $this;

        $formMapper->add('title');
        $formMapper->add('url');
        $formMapper->add(
            'media',
            ModelListType::class,
            [
                'class' => Media::class,
                'required' => false,
                'model_manager' => $admin->getModelManager(),
            ],
            [
                "link_parameters" => [
                    'context' => 'cms_page',
                    'provider' => 'sonata.media.provider.image',
                ],
            ]
        );
        $formMapper->add(
            'description',
            SimpleFormatterType::class,
            [
                'format' => 'richhtml',
                'ckeditor_context' => 'cms_page',
                'required' => false,
                'auto_initialize' => false,
            ]
        );
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('title');
    }
}
