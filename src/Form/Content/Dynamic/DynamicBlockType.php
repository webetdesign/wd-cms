<?php

namespace WebEtDesign\CmsBundle\Form\Content\Dynamic;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Factory\BlockFactory;
use WebEtDesign\CmsBundle\Form\Transformer\CmsBlockTransformer;

class DynamicBlockType extends AbstractType
{
    public function __construct(
        private BlockFactory $blockFactory
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['block_config']) {
            $block = $this->blockFactory->get($options['block_config']);
            $opts  = $block->getFormOptions();

            if (isset($opts['base_block_config']) && $opts['base_block_config']) {
                $opts['base_block_config'] = $options['block_config'];
            }

            $builder->add($block->getCode(), $block->getFormType(), $opts);

            $builder
                ->addModelTransformer(new CallbackTransformer(
                    function ($value) use ($options) {
                        $block = $this->blockFactory->get($options['block_config']);
                        return [$block->getCode() => $value];
                    },
                    function ($value) use ($options) {
                        $block = $this->blockFactory->get($options['block_config']);
                        return $value[$block->getCode()];
                    }
                ));
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['block_config']) {
            $block               = $this->blockFactory->get($options['block_config']);
            $view->vars['block'] = $block;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'block_config'      => null,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'admin_cms_dynamic_block';
    }


}
