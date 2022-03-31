<?php

namespace WebEtDesign\CmsBundle\CmsTemplate;

use WebEtDesign\CmsBundle\DependencyInjection\Models\BlockDefinition;

class AbstractSharedBlock implements TemplateInterface
{
    protected ?string $template = null;
    protected ?string $label    = null;
    protected ?string $code    = null;

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

    /**
     * @param string|null $template
     * @return AbstractSharedBlock
     */
    public function setTemplate(?string $template): TemplateInterface
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
     * @param string|null $code
     * @return AbstractSharedBlock
     */
    public function setCode(?string $code): AbstractSharedBlock
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
