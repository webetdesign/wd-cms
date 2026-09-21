<?php

namespace WebEtDesign\CmsBundle\CMS\Block;

use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\CmsBundle\Form\Type\CmsPageEntityType;

#[AsCmsBlock(self::code)]
class CmsPageBlock extends AbstractBlock
{
    public const code = 'CMS_PAGE_BLOCK';

    protected string $formType = CmsPageEntityType::class;
}
