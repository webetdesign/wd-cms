<?php

namespace WebEtDesign\CmsBundle\CmsTemplate;

use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\DependencyInjection\Models\BlockDefinition;
use WebEtDesign\CmsBundle\DependencyInjection\Models\RouteDefinition;

abstract class AbstractPage implements TemplateInterface, PageInterface
{
    public bool       $section    = false;
    protected ?string $template   = null;
    protected ?string $label      = null;
    protected ?string $code       = null;

    public function getLabel(): string
    {
        return $this->label ?: $this->code;
    }

    /**
     * @return BlockDefinition[]
     */
    public function getBlocks(): iterable
    {
        return [];
    }

    public function getBlock(string $code): ?BlockDefinition
    {
        foreach ($this->getBlocks() as $block) {
            if ($block->getCode() === $code) {
                return $block;
            }
        };
        return null;
    }

    public function getRoute(): ?RouteDefinition
    {
        return null;
    }

    /**
     * @param string|null $template
     * @return AbstractPage
     */
    public function setTemplate(?string $template): AbstractPage
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getCollections(): ?array
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isSection(): bool
    {
        return $this->section;
    }

    /**
     * @param string|null $code
     * @return AbstractPage
     */
    public function setCode(?string $code): TemplateInterface
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

}
