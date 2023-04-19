<?php

namespace WebEtDesign\CmsBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsCmsTemplate
{
    public function __construct(
        public string $code,
        public string $type
    ) {
    }
}
