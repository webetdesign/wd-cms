<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Form\Content\Dynamic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DynamicBlockLoaderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('disc', HiddenType::class, [
            'data' => $options['block_config']?->getCode()
        ]);

        $builder->add('position', HiddenType::class, [
            'data' => $builder->getName(),
            'attr' => [
                'data-cms-adbc-target' => 'positionField',
                'data-index'           => $builder->getName(),
            ]
        ]);

        $builder->add('value', DynamicBlockType::class, [
            'block_config'      => $options['block_config'],
            'label'             => false,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['block_config'] = $options['block_config'];
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'block_config' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'admin_cms_dynamic_block_loader';
    }
}
