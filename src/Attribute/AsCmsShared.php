<?php

namespace WebEtDesign\CmsBundle\Attribute;

use Attribute;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

#[Attribute(Attribute::TARGET_CLASS)]
class AsCmsShared
{
    public function __construct(
        public string $code,
        public string $type = TemplateRegistry::TYPE_SHARED,
    ) {
    }
}
