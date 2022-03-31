<?php

namespace WebEtDesign\CmsBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class AsCmsSharedBlock
{
    public function __construct(
        public string $code,
    ) {
    }
}
