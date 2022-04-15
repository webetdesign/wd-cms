<?php

namespace WebEtDesign\CmsBundle\CmsBlock;

use Symfony\Component\Form\DataTransformerInterface;
use WebEtDesign\CmsBundle\DependencyInjection\Models\BlockDefinition;

interface BlockInterface
{

    public function getModelTransformer(): ?DataTransformerInterface;

    public function getCode(): ?string;

    public function setCode(?string $code): ?BlockInterface;

    public function getLabel(): ?string;

    public function setLabel(?string $label): BlockInterface;

    public function getTemplate(): ?string;

    public function setTemplate(?string $template): BlockInterface;

    public function getHelp(): ?string;

    public function setHelp(?string $help): BlockInterface;

    public function getFormType(): string;

    public function setFormType(string $formType): BlockInterface;

    public function getFormTheme(): ?string;

    public function setFormTheme(?string $formTheme): BlockInterface;

    public function setSettings(array $settings): BlockInterface;

    public function getSettings(): array;

    public function setFormOptions(array $formOptions): BlockInterface;

    public function getFormOptions(): array;

    public function setOpen(bool $open): BlockInterface;

    public function isOpen(): bool;

    public function setBlocks(array $blocks): BlockInterface;

    public function getBlocks(): array;

    public function setAvailableBlocks(array $availableBlocks): BlockInterface;

    public function getAvailableBlocks(): array;

    public function getAvailableBlock(string $code): ?BlockDefinition;
}
