<?php

namespace WebEtDesign\CmsBundle\CMS\Template;


use WebEtDesign\CmsBundle\CMS\Configuration\RouteDefinition;
use WebEtDesign\CmsBundle\Vars\CmsVarsBag;

interface PageInterface
{
    public function getRoute(): ?RouteDefinition;
}
