<?php

namespace WebEtDesign\CmsBundle\CmsBlock;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use WebEtDesign\CmsBundle\DependencyInjection\Models\BlockDefinition;
use WebEtDesign\CmsBundle\Factory\BlockFactory;

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
    ];

    protected ?string $formTheme = null;

    protected bool $open = false;

    protected array $blocks = [];

    protected array $availableBlocks = [];

    protected ?Environment $twig = null;

    protected BlockFactory $factory;

    protected array $options = [];

    public function render($value, ?array $context = null)
    {
        $transformer = $this->getModelTransformer();

        $value = $transformer->transform($value, true);

        if (!empty($this->getTemplate())) {
            if ($value === null) {
                return null;
            }
            if (is_object($value)) {
                $value = ['object' => $value];
            }
            if (is_string($value)) {
                $value = ['value' => $value];
            }
            if (!empty($this->getSettings())) {
                $value = array_merge($value, ['settings' => $this->getSettings()]);
            }
            if (!empty($context)) {
                $value = array_merge_recursive($value, $context);
            }

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
     * @return BlockDefinition[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function getBlock(string $code): ?BlockDefinition
    {
        foreach ($this->getBlocks() as $block) {
            if ($block->getCode() === $code) {
                return $block;
            }
        }

        return null;
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

    /**
     * @param array $options
     * @return AbstractBlock
     */
    public function setOptions(array $options): AbstractBlock
    {
        $resolver = new OptionsResolver();

        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'use_row'               => false,
            'row_col_class'         => null,
            'use_accordion'         => false,
            'new_row_on_next_block' => false,
        ]);
    }
}
