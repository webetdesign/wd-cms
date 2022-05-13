<?php

namespace WebEtDesign\CmsBundle\CmsBlock;

use Symfony\Component\Form\Extension\Core\Type\ColorType;
use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\CmsBundle\CmsBlock\AbstractBlock;

#[AsCmsBlock(name: self::code)]
class ColorBlock extends AbstractBlock
{
    public const code = 'COLOR';

    protected string $formType = ColorType::class;
}
