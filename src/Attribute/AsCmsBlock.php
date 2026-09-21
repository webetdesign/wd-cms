<?php

namespace WebEtDesign\CmsBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class AsCmsBlock
{
    public function __construct(
        public string $name,
        public ?string $formTheme = null,
    ) {
    }
}
