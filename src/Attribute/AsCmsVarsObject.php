<?php

namespace WebEtDesign\CmsBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class AsCmsVarsObject
{
    public function __construct(public ?string $name = null) {}
}
