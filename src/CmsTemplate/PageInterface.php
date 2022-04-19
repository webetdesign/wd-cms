<?php

namespace WebEtDesign\CmsBundle\CmsTemplate;

use WebEtDesign\CmsBundle\DependencyInjection\Models\RouteDefinition;

interface PageInterface
{
    public function isSection(): bool;

    public function getRoute(): ?RouteDefinition;
}
