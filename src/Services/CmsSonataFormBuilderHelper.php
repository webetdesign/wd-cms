<?php

namespace WebEtDesign\CmsBundle\Services;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Symfony\Component\Form\FormBuilderInterface;

class CmsSonataFormBuilderHelper
{
    /**
     * @var Pool
     */
    private Pool $adminPool;

    public function __construct(Pool $adminPool)
    {
        $this->adminPool = $adminPool;
    }

    public function buildModelListType(
        FormBuilderInterface $builder,
                             $fieldName,
                             $parentClass,
                             $childClass,
                             $options = []
    )
    {
        $parentAdmin = $this->adminPool->getAdminByClass($parentClass);
        $childAdmin  = $this->adminPool->getAdminByClass($childClass);

        $linkParameters = ['context' => 'default'];
        if (isset($options['link_parameters'])) {
            $linkParameters = $options['link_parameters'];
        }

        /** @var FieldDescription $fieldDescription */
        $fieldDescription = $childAdmin->getFieldDescriptionFactory()->create($childClass, $fieldName, [
            'translation_domain' => $options['translation_domain'] ?? 'wd_cms',
            'link_parameters'    => $linkParameters
        ]);

        $fieldDescription->setAdmin($parentAdmin);
        $fieldDescription->setAssociationAdmin($childAdmin);
        $fieldDescription->setOption('edit', 'list');

        $opts = [
            'model_manager'            => $childAdmin->getModelManager(),
            'sonata_field_description' => $fieldDescription,
            'class'                    => $childClass,
            'required'                 => false,
            'label'                    => $options['label'] ?? null,
        ];

        if (isset($linkParameters['btn_delete'])) {
            $opts['btn_delete'] = $linkParameters['btn_delete'];
        }
        if (isset($linkParameters['btn_edit'])) {
            $opts['btn_edit'] = $linkParameters['btn_edit'];
        }
        if (isset($linkParameters['btn_list'])) {
            $opts['btn_list'] = $linkParameters['btn_list'];
        }
        if (isset($linkParameters['btn_add'])) {
            $opts['btn_add'] = $linkParameters['btn_add'];
        }

        $builder->add($fieldName,
            ModelListType::class,
            $opts
        );
    }

}
