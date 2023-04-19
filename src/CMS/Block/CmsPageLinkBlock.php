<?php

namespace WebEtDesign\CmsBundle\CMS\Block;

use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\CmsBundle\DependencyInjection\Models\BlockDefinition;
use WebEtDesign\CmsBundle\Form\Type\CmsPageEntityType;
use WebEtDesign\CmsBundle\Form\Type\CmsPageLinkType;

#[AsCmsBlock(self::code)]
class CmsPageLinkBlock extends AbstractBlock
{
    public const code = 'CMS_PAGE_LINK_BLOCK';

    protected string $formType = CmsPageLinkType::class;
}
