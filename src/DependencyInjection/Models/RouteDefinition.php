<?php

namespace WebEtDesign\CmsBundle\DependencyInjection\Models;

use Symfony\Component\HttpFoundation\Request;

class RouteDefinition
{
    protected ?string $path       = null;
    protected ?string $name       = null;
    protected ?string $controller = null;
    protected array   $attributes = [];
    protected array   $methods    = [Request::METHOD_GET];

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
}
