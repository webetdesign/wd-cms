<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="cms__route")
 * @ORM\Entity(repositoryClass="App\Repository\CmsRouteRepository")
 * @UniqueEntity("path")
 */
class CmsRoute
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="array")
     */
    private $methods = [];

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $path;

    /**
     * @var null|string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $controller;

    /**
     * @var CmsPage
     *
     * @ORM\OneToOne(targetEntity="CmsPage", mappedBy="route")
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
