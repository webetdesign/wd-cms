<?php

namespace WebEtDesign\CmsBundle\CMS\Template;


use WebEtDesign\CmsBundle\CMS\Configuration\RouteDefinition;
use WebEtDesign\CmsBundle\Vars\CmsVarsBag;

abstract class AbstractPage extends AbstractComponent implements PageInterface
{
    public function getRoute(): ?RouteDefinition
    {
        return RouteDefinition::new();
    }
}
