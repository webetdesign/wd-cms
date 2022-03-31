<?php

namespace WebEtDesign\CmsBundle\Form\Content\Dynamic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DynamicBlockLoaderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('disc', HiddenType::class, [
            'data' => $options['block_config']?->getCode()
        ]);

        $builder->add('position', HiddenType::class, [
            'data' => $builder->getName(),
            'attr' => [
                'data-adbc-target' => 'positionField',
                'data-index'       => $builder->getName(),
            ]
        ]);

        $builder->add('value', DynamicBlockType::class, [
            'block_config' => $options['block_config'],
            'label'        => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'block_config' => null,
        ]);
    }

    public function getBlockPrefix()
    {
        return "admin_cms_dynamic_block_loader";
    }
}
