<?php

namespace WebEtDesign\CmsBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @UniqueEntity("path")
 */
abstract class AbstractCmsRoute implements CmsRouteInterface
{
    /**
     */
    private $id;

    /**
     */
    private $name;

    /**
     */
    private $methods = [];

    /**
     */
    private $path;

    /**
     * @var null|string
     *
     */
    private $controller;

    /**
     * @var CmsPage
     *
     */
    private $page;

    /**
     * @var string
     *
     */
    private $defaults;

    /** @var string */
    private $requirements;

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string) $this->getName();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMethods(): ?array
    {
        return $this->methods;
    }

    public function setMethods(array $methods): self
    {
        $this->methods = $methods;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return CmsPage
     */
    public function getPage(): ?CmsPage
    {
        return $this->page;
    }

    /**
     * @param CmsPage $page
     */
    public function setPage(?CmsPage $page): void
    {
        $this->page = $page;
    }

    /**
     * @return string
     */
    public function getController(): ?string
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     */
    public function setController(?string $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * @return string
     */
    public function getDefaults(): ?string
    {
        return $this->defaults;
    }

    /**
     * @param string $defaults
     * @return self
     */
    public function setDefaults(?string $defaults): self
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequirements(): ?string
    {
        return $this->requirements;
    }

    /**
     * @param string $requirements
     * @return self
     */
    public function setRequirements(?string $requirements): self
    {
        $this->requirements = $requirements;
        return $this;
    }

}
