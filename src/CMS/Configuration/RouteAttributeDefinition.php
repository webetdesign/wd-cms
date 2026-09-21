<?php

namespace WebEtDesign\CmsBundle\CMS\Configuration;

class RouteAttributeDefinition
{
    protected ?string         $name           = null;
    protected ?string         $entityClass    = null;
    protected ?string         $entityProperty = null;
    protected ?string         $requirement    = null;
    protected null|string|int $default        = null;
    protected ?string         $formType       = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function new($name): RouteAttributeDefinition
    {
        return new self($name);
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return RouteAttributeDefinition
     */
    public function setName(?string $name): RouteAttributeDefinition
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    /**
     * @param string|null $entityClass
     * @return RouteAttributeDefinition
     */
    public function setEntityClass(?string $entityClass): RouteAttributeDefinition
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequirement(): ?string
    {
        return $this->requirement;
    }

    /**
     * @param string|null $requirement
     * @return RouteAttributeDefinition
     */
    public function setRequirement(?string $requirement): RouteAttributeDefinition
    {
        $this->requirement = $requirement;

        return $this;
    }

    /**
     * @return int|string|null
     */
    public function getDefault(): int|string|null
    {
        return $this->default;
    }

    /**
     * @param int|string|null $default
     * @return RouteAttributeDefinition
     */
    public function setDefault(int|string|null $default): RouteAttributeDefinition
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @param string|null $entityProperty
     * @return RouteAttributeDefinition
     */
    public function setEntityProperty(?string $entityProperty): RouteAttributeDefinition
    {
        $this->entityProperty = $entityProperty;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEntityProperty(): ?string
    {
        return $this->entityProperty;
    }

    /**
     * @return string|null
     */
    public function getFormType(): ?string
    {
        return $this->formType;
    }

    /**
     * @param string|null $formType
     * @return RouteAttributeDefinition
     */
    public function setFormType(?string $formType): RouteAttributeDefinition
    {
        $this->formType = $formType;

        return $this;
    }

}
