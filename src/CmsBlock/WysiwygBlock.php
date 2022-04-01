<?php

namespace WebEtDesign\CmsBundle\CmsBlock;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;

#[AsCmsBlock(name: self::code)]
class WysiwygBlock extends AbstractBlock
{
    const code = 'WYSIWYG';

    protected string $formType = CKEditorType::class;

    protected array $formOptions = [
        'required'    => false,
        'config_name' => 'default',
    ];
}
