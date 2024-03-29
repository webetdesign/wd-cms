<?php

namespace WebEtDesign\CmsBundle\CMS\Configuration;

use Symfony\Component\HttpFoundation\Request;

class RouteDefinition
{
    protected ?string $path       = null;
    protected ?string $name       = null;
    protected ?string $controller = null;
    protected ?string $action     = null;
    protected array   $attributes = [];
    protected array   $methods    = [Request::METHOD_GET];
    protected int     $priority   = 0;

    public static function new(): RouteDefinition
    {
        return new self();
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return RouteAttributeDefinition[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param $name
     * @return ?RouteAttributeDefinition
     */
    public function getAttribute($name): ?RouteAttributeDefinition
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->getName() === $name) {
                return $attribute;
            }
        }
        return null;
    }

    /**
     * @param string|null $path
     * @return RouteDefinition
     */
    public function setPath(?string $path): RouteDefinition
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param string|null $name
     * @return RouteDefinition
     */
    public function setName(?string $name): RouteDefinition
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param RouteAttributeDefinition[] $attributes
     * @return RouteDefinition
     */
    public function setAttributes(array $attributes): RouteDefinition
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @param array $methods
     * @return RouteDefinition
     */
    public function setMethods(array $methods): RouteDefinition
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param string|null $controller
     * @return RouteDefinition
     */
    public function setController(?string $controller): RouteDefinition
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getController(): ?string
    {
        return $this->controller;
    }

    /**
     * @param string|null $action
     * @return RouteDefinition
     */
    public function setAction(?string $action): RouteDefinition
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }
}
