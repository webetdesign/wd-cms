<?php


use Sonata\DatagridBundle\Filter\FilterInterface;
use Sonata\DatagridBundle\ProxyQuery\ProxyQueryInterface;

class HiddenFilterType implements FilterInterface
{

    /**
     * @inheritDoc
     */
    public function filter(
        ProxyQueryInterface $queryBuilder,
        string $alias,
        string $field,
        string $value
    ): void {
        // TODO: Implement filter() method.
    }

    /**
     * @inheritDoc
     */
    public function apply($query, $value): void
    {
        // TODO: Implement apply() method.
    }

    public function getName(): ?string
    {
        // TODO: Implement getName() method.
    }

    public function getFormName(): string
    {
        // TODO: Implement getFormName() method.
    }

    public function getLabel(): ?string
    {
        // TODO: Implement getLabel() method.
    }

    public function setLabel(string $label): void
    {
        // TODO: Implement setLabel() method.
    }

    public function getDefaultOptions(): array
    {
        // TODO: Implement getDefaultOptions() method.
    }

    /**
     * @inheritDoc
     */
    public function getOption(string $name, $default = null)
    {
        // TODO: Implement getOption() method.
    }

    /**
     * @inheritDoc
     */
    public function setOption(string $name, $value): void
    {
        // TODO: Implement setOption() method.
    }

    public function initialize(string $name, array $options = []): void
    {
        // TODO: Implement initialize() method.
    }

    public function getFieldName(): string
    {
        // TODO: Implement getFieldName() method.
    }

    public function getFieldOptions(): array
    {
        // TODO: Implement getFieldOptions() method.
    }

    public function getFieldType(): string
    {
        // TODO: Implement getFieldType() method.
    }

    /**
     * @inheritDoc
     */
    public function getRenderSettings(): array
    {
        // TODO: Implement getRenderSettings() method.
    }

    public function isActive(): bool
    {
        // TODO: Implement isActive() method.
    }

    /**
     * @inheritDoc
     */
    public function setCondition(string $condition): void
    {
        // TODO: Implement setCondition() method.
    }

    public function getCondition(): string
    {
        // TODO: Implement getCondition() method.
    }

    public function getTranslationDomain(): string
    {
        // TODO: Implement getTranslationDomain() method.
    }
}
