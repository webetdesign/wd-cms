<?php

namespace WebEtDesign\CmsBundle\CMS\Block;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\CmsBundle\Form\Transformer\CheckboxTransformer;

#[AsCmsBlock(name: self::code)]
class CheckboxBlock extends AbstractBlock
{
    public const code = 'CHECKBOX';

    protected string $formType = CheckboxType::class;

    public function getModelTransformer(): ?DataTransformerInterface
    {
        return new CheckboxTransformer;
    }
}
