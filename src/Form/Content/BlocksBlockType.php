<?php

namespace WebEtDesign\CmsBundle\Form\Content;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Factory\BlockFactory;

class BlocksBlockType extends AbstractType
{
    public function __construct(private BlockFactory $blockFactory) { }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['base_block_config']) {
            $block = $this->blockFactory->get($options['base_block_config']);

            foreach ($block->getBlocks() as $blockConfig) {
                $childBlock = $this->blockFactory->get($blockConfig);

                $opts = $childBlock->getFormOptions();

                if (isset($opts['base_block_config']) && $opts['base_block_config']) {
                    $opts['base_block_config'] = $blockConfig;
                }

                if (!isset($opts['label'])) {
                    $opts['label'] = $childBlock->getLabel();
                }

                $builder->add($childBlock->getCode(), $childBlock->getFormType(), $opts);
            }
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $block = $this->blockFactory->get($options['base_block_config']);
        $configs = [];
        foreach ($block->getBlocks() as $blockDefinition) {
            $configs[$blockDefinition->getCode()] = $blockDefinition;
        }

        $view->vars['block_configs'] = $configs;
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'base_block_config' => null,
        ]);
    }


    public function getBlockPrefix(): string
    {
        return 'cms_blocks_block';
    }

}
