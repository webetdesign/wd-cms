<?php

namespace WebEtDesign\CmsBundle\CmsBlock;

use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Form\Content\Dynamic\DynamicBlockCollectionType;

#[AsCmsBlock(name: self::code, formTheme: '@WebEtDesignCms/admin/form/dynamic_block.html.twig')]
class DynamicBlock extends AbstractBlock
{
    public const code = 'DYNAMIC';

    protected string $formType = DynamicBlockCollectionType::class;

    protected array $formOptions = [
        'base_block' => true,
    ];

    public function render(CmsContent $content)
    {
        $transformer = $this->getModelTransformer();

        $values = $transformer->transform($content->getValue(), true);

        $blocks = [];
        foreach ($values as $blockData) {
            $blocks[$blockData['disc']] = $blockData['value'];
        }

        if (!empty($this->getTemplate())) {
            return $this->getTwig()->render($this->getTemplate(), $blocks);
        }

        return $blocks;
    }

}
