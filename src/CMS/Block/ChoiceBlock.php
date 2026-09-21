<?php

namespace WebEtDesign\CmsBundle\CMS\Block;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;

#[AsCmsBlock(name: self::code)]
class ChoiceBlock extends AbstractBlock
{
    public const code = 'CHOICE';

    protected string $formType = ChoiceType::class;
}
