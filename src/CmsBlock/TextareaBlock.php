<?php

namespace WebEtDesign\CmsBundle\CmsBlock;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;

#[AsCmsBlock(name: self::code)]
class TextareaBlock extends AbstractBlock
{
    public const code = 'TEXTAREA';

    protected string $formType = TextareaType::class;
}
