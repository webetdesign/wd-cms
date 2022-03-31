<?php

namespace WebEtDesign\CmsBundle\DependencyInjection\Models;

class BlockDefinition
{

    protected bool    $open            = false;
    protected ?string $help            = null;
    protected array   $formOptions     = [];
    protected array   $settings        = [];
    protected array   $blocks          = [];
    protected array   $availableBlocks = [];
    protected ?string $template        = null;

    public function __construct(
        protected string $code,
        protected string $type,
        protected ?string $label = null,
    ) {
    }

    public static function new(string $code, string $type, ?string $label = null): self
    {
        return new self($code, $type, $label);
    }

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label ?: $this->code;
    }

    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * @param bool $open
     * @return BlockDefinition
     */
    public function setOpen(bool $open): BlockDefinition
    {
        $this->open = $open;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHelp(): ?string
    {
        return $this->help;
    }

    /**
     * @param string|null $help
     * @return BlockDefinition
     */
    public function setHelp(?string $help): BlockDefinition
    {
        $this->help = $help;
        return $this;
    }

    /**
     * @return array
     */
    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    /**
     * @param array $formOptions
     * @return BlockDefinition
     */
    public function setFormOptions(array $formOptions): BlockDefinition
    {
        $this->formOptions = $formOptions;
        return $this;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     * @return BlockDefinition
     */
    public function setSettings(array $settings): BlockDefinition
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * @return array
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @param array $blocks
     * @return BlockDefinition
     */
    public function setBlocks(array $blocks): BlockDefinition
    {
        $this->blocks = $blocks;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string|null $template
     * @return BlockDefinition
     */
    public function setTemplate(?string $template): BlockDefinition
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

    /**
     * @return array
     */
    public function getAvailableBlocks(): array
    {
        return $this->availableBlocks;
    }

    /**
     * @param array $availableBlocks
     * @return BlockDefinition
     */
    public function setAvailableBlocks(array $availableBlocks): BlockDefinition
    {
        $this->availableBlocks = $availableBlocks;
        return $this;
    }
}
