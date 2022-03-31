<?php

namespace WebEtDesign\CmsBundle\CmsBlock;

use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\CmsBundle\Form\Content\AdminCmsBlocksType;

#[AsCmsBlock(name: self::code)]
class BlocksBlock extends AbstractBlock
{
    public const code = 'BLOCKS';

    protected string $formType = AdminCmsBlocksType::class;

    protected array $formOptions = [
        'base_block' => true,
    ];

    protected bool $compound = true;

}
