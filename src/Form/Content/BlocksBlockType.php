<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Form\Content;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Registry\BlockRegistry;

class BlocksBlockType extends AbstractType
{
    public function __construct(private BlockRegistry $blockRegistry) { }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['base_block_config']) {
            $block = $this->blockRegistry->get($options['base_block_config']);

            foreach ($block->getBlocks() as $blockConfig) {
                $childBlock = $this->blockRegistry->get($blockConfig);

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

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $block = $this->blockRegistry->get($options['base_block_config']);
        $configs = [];
        $blocks = [];
        foreach ($block->getBlocks() as $blockDefinition) {
            $configs[$blockDefinition->getCode()] = $blockDefinition;
            $blocks[$blockDefinition->getCode()] = $this->blockRegistry->get($blockDefinition);
        }

        $view->vars['blocks'] = $blocks;
        $view->vars['block_configs'] = $configs;
    }


    public function configureOptions(OptionsResolver $resolver): void
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
