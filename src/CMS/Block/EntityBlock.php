<?php

namespace WebEtDesign\CmsBundle\CMS\Block;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;

#[AsCmsBlock(name: self::code)]
class EntityBlock extends AbstractBlock
{
    public const code = 'ENTITY';

    protected string $formType = EntityType::class;
}
