<?php

namespace WebEtDesign\CmsBundle\CMS\Template;


use WebEtDesign\CmsBundle\CMS\Configuration\RouteDefinition;
use WebEtDesign\CmsBundle\Vars\CmsVarsBag;

abstract class AbstractPage extends AbstractComponent implements PageInterface
{
    public bool $section = false;

    public function getRoute(): ?RouteDefinition
    {
        return RouteDefinition::new();
    }

    /**
     * @return bool
     */
    public function isSection(): bool
    {
        return $this->section;
    }

    /**
     * @param bool $section
     * @return AbstractPage
     */
    public function setSection(bool $section): AbstractPage
    {
        $this->section = $section;
        return $this;
    }
}
