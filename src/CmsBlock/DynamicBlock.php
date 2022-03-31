<?php

namespace WebEtDesign\CmsBundle\CmsBlock;

use App\Entity\User\User;
use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\CmsBundle\Form\Content\Dynamic\DynamicBlockCollectionType;

#[AsCmsBlock(name: self::code, formTheme: '@WebEtDesignCms/admin/form/dynamic_block.html.twig')]
class DynamicBlock extends AbstractBlock
{
    public const code = 'DYNAMIC';

    protected string $formType = DynamicBlockCollectionType::class;

    protected array $formOptions = [
        'base_block' => true,
    ];

}
