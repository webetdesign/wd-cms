<?php

namespace WebEtDesign\CmsBundle\CmsBlock;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Twig\Environment;
use WebEtDesign\CmsBundle\DependencyInjection\Models\BlockDefinition;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Factory\BlockFactory;
use WebEtDesign\CmsBundle\Form\Transformer\CmsBlockTransformer;

abstract class AbstractBlock implements BlockInterface
{
    const code = 'TEXT';

    protected ?string $code = null;

    protected ?string $label = null;

    protected array $settings = [];

    protected ?string $template = null;

    protected ?string $help = null;

    protected string $formType = TextType::class;

    protected ?DataTransformerInterface $modelTransformer;

    protected array $formOptions = [
        'required' => false,
//        'label'    => false,
    ];

    protected ?string $formTheme = null;

    protected bool $open = false;

    protected array $blocks = [];

    protected array $availableBlocks = [];

    protected bool $compound = false;

    protected ?Environment $twig = null;

    protected BlockFactory $factory;

    public function render(CmsContent $content)
    {
        $transformer = $this->getModelTransformer();

        $value = $transformer->transform($content->getValue(), true);

        if (!empty($this->getTemplate())) {
            $value = $this->getTwig()->render($this->getTemplate(), $value);
        }

        return $value;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string|null $code
     * @return AbstractBlock
     */
    public function setCode(?string $code): AbstractBlock
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string|null $label
     * @return AbstractBlock
     */
    public function setLabel(?string $label): AbstractBlock
    {
        $this->label = $label;
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
     * @param string|null $template
     * @return AbstractBlock
     */
    public function setTemplate(?string $template): AbstractBlock
    {
        $this->template = $template;
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
     * @return AbstractBlock
     */
    public function setHelp(?string $help): AbstractBlock
    {
        $this->help = $help;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormType(): string
    {
        return $this->formType;
    }

    /**
     * @param string $formType
     * @return AbstractBlock
     */
    public function setFormType(string $formType): AbstractBlock
    {
        $this->formType = $formType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFormTheme(): ?string
    {
        return $this->formTheme;
    }

    /**
     * @param string|null $formTheme
     * @return AbstractBlock
     */
    public function setFormTheme(?string $formTheme): AbstractBlock
    {
        $this->formTheme = $formTheme;
        return $this;
    }

    /**
     * @param array $settings
     * @return AbstractBlock
     */
    public function setSettings(array $settings): AbstractBlock
    {
        $this->settings = $settings;
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
     * @param array $formOptions
     * @return AbstractBlock
     */
    public function setFormOptions(array $formOptions): AbstractBlock
    {
        $this->formOptions = $formOptions;
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
     * @param bool $open
     * @return AbstractBlock
     */
    public function setOpen(bool $open): AbstractBlock
    {
        $this->open = $open;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * @param array $blocks
     * @return AbstractBlock
     */
    public function setBlocks(array $blocks): AbstractBlock
    {
        $this->blocks = $blocks;
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
     * @param bool $compound
     * @return AbstractBlock
     */
    public function setCompound(bool $compound): AbstractBlock
    {
        $this->compound = $compound;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCompound(): bool
    {
        return $this->compound;
    }

    /**
     * @param array $availableBlocks
     * @return AbstractBlock
     */
    public function setAvailableBlocks(array $availableBlocks): AbstractBlock
    {
        $this->availableBlocks = $availableBlocks;
        return $this;
    }

    /**
     * @return BlockDefinition[]
     */
    public function getAvailableBlocks(): array
    {
        return $this->availableBlocks;
    }

    public function getAvailableBlock(string $code): ?BlockDefinition
    {
        foreach ($this->getAvailableBlocks() as $availableBlock) {
            if ($availableBlock->getCode() === $code) {
                return $availableBlock;
            }
        }

        return null;
    }

    /**
     * @param Environment|null $twig
     * @return AbstractBlock
     */
    public function setTwig(?Environment $twig): AbstractBlock
    {
        $this->twig = $twig;
        return $this;
    }

    /**
     * @return Environment|null
     */
    public function getTwig(): ?Environment
    {
        return $this->twig;
    }

    /**
     * @param DataTransformerInterface|null $modelTransformer
     * @return AbstractBlock
     */
    public function setModelTransformer(?DataTransformerInterface $modelTransformer): AbstractBlock
    {
        $this->modelTransformer = $modelTransformer;
        return $this;
    }

    public function getModelTransformer(): ?DataTransformerInterface
    {
        return $this->modelTransformer;
    }

    /**
     * @param BlockFactory $factory
     * @return AbstractBlock
     */
    public function setFactory(BlockFactory $factory): AbstractBlock
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * @return BlockFactory
     */
    public function getFactory(): BlockFactory
    {
        return $this->factory;
    }
}
