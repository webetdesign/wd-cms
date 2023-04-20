<?php

namespace WebEtDesign\CmsBundle\CMS\Block;

use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\CmsBundle\Form\Content\Dynamic\DynamicBlockCollectionType;

#[AsCmsBlock(name: self::code, formTheme: '@WebEtDesignCms/admin/form/dynamic_block.html.twig')]
class DynamicBlock extends AbstractBlock
{
    public const code = 'DYNAMIC';

    protected string $formType = DynamicBlockCollectionType::class;

    protected array $formOptions = [
        'required'          => false,
        'base_block_config' => true,
    ];

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'use_accordion' => true,
        ]);
    }

    public function render($value, ?array $context = null)
    {
        $transformer = $this->getModelTransformer();

        $values = $transformer->transform($value, true);

        if (null === $values) {
            return null;
        }

        $blocks = [];
        foreach ($values as $key => $blockData) {
            $config = $this->getAvailableBlock($blockData['disc']);
            if (!$config) {
                continue;
            }
            $block = $this->getRegistry()->get($config);

            $context['block_loop'] = [
                'index' => $key,
                'first' => $key === 0,
                'last'  => $key === array_key_last($values)
            ];

            $context['block_config'] = $config;

            $blocks[$key . '_' . $blockData['disc']] = $block->render($blockData['value'],
                $context);
        }


        if (!empty($this->getTemplate())) {
            if (empty($context)) {
                $context = [];
            }
            if (!empty($this->getSettings())) {
                $context = array_merge($context, ['settings' => $this->getSettings()]);
            }
            $context['blocks'] = $blocks;
            return $this->getTwig()->render($this->getTemplate(), $context);
        }

        return $blocks;
    }
}
