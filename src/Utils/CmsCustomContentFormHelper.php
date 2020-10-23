<?php


namespace WebEtDesign\CmsBundle\Utils;


use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Symfony\Component\Form\FormBuilderInterface;

trait CmsCustomContentFormHelper
{

    public function buildModelListType(
        FormBuilderInterface $builder,
        AdminInterface $cmsContentAdmin,
        AdminInterface $adminClass,
        $filedName,
        $class,
        $options = []
    ) {
        $linkParameters = ['context' => 'default'];
        if (isset($options['link_parameters'])) {
            $linkParameters = $options['link_parameters'];
        }

        /** @var FieldDescription $fieldDescription */
        $fieldDescription = $adminClass
            ->getModelManager()
            ->getNewFieldDescriptionInstance($adminClass->getClass(), $filedName, [
                'translation_domain' => $options['translation_domain'] ?? 'wd_cms',
                'link_parameters'    => $linkParameters
            ]);
        $fieldDescription->setAssociationAdmin($adminClass);
        $fieldDescription->setAdmin($cmsContentAdmin);
        $fieldDescription->setOption('edit', 'list');
        $fieldDescription->setAssociationMapping([
            'fieldName' => $filedName,
            'type'      => ClassMetadataInfo::MANY_TO_ONE,
        ]);

        $opts = [
            'model_manager'            => $adminClass->getModelManager(),
            'sonata_field_description' => $fieldDescription,
            'class'                    => $class,
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

        $builder->add($filedName,
            ModelListType::class,
            $opts
        );
    }

}
