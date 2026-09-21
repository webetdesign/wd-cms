<?php

namespace WebEtDesign\CmsBundle\CMS\Configuration;

class VarDefinition
{
    protected string  $code;
    protected string  $class;
    protected ?string $name              = null;
    protected ?string $routeAttributeKey = null;
    protected ?string $findOneBy         = null;

    /**
     * @param string $code
     * @param string $class FQCN of the object
     */
    public function __construct(string $code, string $class)
    {
        $this->code  = $code;
        $this->class = $class;
    }

    public static function new(string $code, string $class, ?string $name = null, ?string $routeAttributeKey = null): VarDefinition
    {
        return (new self($code, $class))
            ->setName($name)
            ->setRouteAttributeKey($routeAttributeKey);
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return VarDefinition
     */
    public function setCode(string $code): VarDefinition
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     * @return VarDefinition
     */
    public function setClass(string $class): VarDefinition
    {
        $this->class = $class;
        return $this;
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
     * @return VarDefinition
     */
    public function setName(?string $name): VarDefinition
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRouteAttributeKey(): ?string
    {
        return $this->routeAttributeKey;
    }

    /**
     * @param string|null $routeAttributeKey
     * @return VarDefinition
     */
    public function setRouteAttributeKey(?string $routeAttributeKey): VarDefinition
    {
        $this->routeAttributeKey = $routeAttributeKey;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFindOneBy(): ?string
    {
        return $this->findOneBy;
    }

    /**
     * @param string|null $findOneBy
     * @return VarDefinition
     */
    public function setFindOneBy(?string $findOneBy): VarDefinition
    {
        $this->findOneBy = $findOneBy;
        return $this;
    }


}
