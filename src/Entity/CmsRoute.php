<?php

namespace WebEtDesign\CmsBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @UniqueEntity("path")
 */
class CmsRoute
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
}
