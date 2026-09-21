<?php

namespace WebEtDesign\CmsBundle\CMS\Template;


use WebEtDesign\CmsBundle\CMS\Configuration\BlockDefinition;
use WebEtDesign\CmsBundle\Vars\CmsVarsBag;

class AbstractComponent implements ComponentInterface
{
    protected ?string     $template = null;
    protected ?string     $label    = null;
    protected ?string     $code     = null;
    protected ?CmsVarsBag $varsBag  = null;

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
     * @return AbstractComponent
     */
    public function setTemplate(?string $template): ComponentInterface
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
     * @return AbstractComponent
     */
    public function setCode(?string $code): AbstractComponent
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

    public function configureVars(CmsVarsBag $varsBag): void { }

    /**
     * @return CmsVarsBag|null
     */
    public function getVarsBag(): ?CmsVarsBag
    {
        return $this->varsBag;
    }

    /**
     * @param CmsVarsBag|null $varsBag
     * @return AbstractComponent
     */
    public function setVarsBag(?CmsVarsBag $varsBag): AbstractComponent
    {
        $this->varsBag = $varsBag;
        return $this;
    }
}
