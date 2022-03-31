<?php

namespace WebEtDesign\CmsBundle\CmsBlock;

use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\MediaBundle\Form\Type\WDMediaType;

#[AsCmsBlock(name: self::code)]
class MediaBlock extends AbstractBlock
{
    const code = 'MEDIA';

    protected string $formType = WDMediaType::class;

    protected array $formOptions = [
        'category' => 'default'
    ];
}
