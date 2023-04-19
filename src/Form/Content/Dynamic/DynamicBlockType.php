<?php

namespace WebEtDesign\CmsBundle\Form\Content\Dynamic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Registry\BlockRegistry;

class DynamicBlockType extends AbstractType
{
    public function __construct(
        private readonly BlockRegistry $blockRegistry
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['block_config']) {
            $block = $this->blockRegistry->get($options['block_config']);
            $opts  = $block->getFormOptions();

            if (isset($opts['base_block_config']) && $opts['base_block_config']) {
                $opts['base_block_config'] = $options['block_config'];
            }

            $builder->add($block->getCode(), $block->getFormType(), $opts);

            $builder
                ->addModelTransformer(new CallbackTransformer(
                    function ($value) use ($options) {
                        $block = $this->blockRegistry->get($options['block_config']);
                        return [$block->getCode() => $value];
                    },
                    function ($value) use ($options) {
                        $block = $this->blockRegistry->get($options['block_config']);
                        return $value[$block->getCode()];
                    }
                ));
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['block_config']) {
            $block               = $this->blockRegistry->get($options['block_config']);
            $view->vars['block'] = $block;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'block_config'      => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'admin_cms_dynamic_block';
    }


}
