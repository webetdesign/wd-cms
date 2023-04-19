<?php

namespace WebEtDesign\CmsBundle\CMS\Block;

use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\CmsBundle\Form\Content\BlocksBlockType;

#[AsCmsBlock(name: self::code)]
class StaticBlock extends AbstractBlock
{
    public const code = 'BLOCKS';

    protected string $formType = BlocksBlockType::class;

    protected array $formOptions = [
        'base_block_config' => true,
    ];

    public function render($value, ?array $context = null)
    {
        $transformer = $this->getModelTransformer();

        $values = $transformer->transform($value, true);

        if (null === $values) {
            return null;
        }

        $context = array_merge($context??[], ["settings" => $this->getSettings()]);

        $blocks = [];
        foreach ($values as $key => $blockData) {
            $config = $this->getBlock($key);
            if (!$config) {
                continue;
            }
            $block = $this->getRegistry()->get($config);
            if ($blockData == null) {
                $blocks[$key] = null;
            } else {
                $blocks[$key] = $block->render($blockData, $context);

            }
        }

        if (!empty($this->getTemplate())) {
            if (empty($context)) {
                $context = [];
            }
            $context = array_merge($context, $blocks);
            return $this->getTwig()->render($this->getTemplate(), $context);
        }

        return $blocks;
    }

}
