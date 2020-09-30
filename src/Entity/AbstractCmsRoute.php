<?php

namespace WebEtDesign\CmsBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;


/**
 * @UniqueEntity("path")
 * @ORM\Entity(repositoryClass="WebEtDesign\CmsBundle\Repository\CmsRouteRepository")
 * @ORM\Table(name="cms__route")
 */
abstract class AbstractCmsRoute implements CmsRouteInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;


    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private $name;


    /**
     * @var array
     * @ORM\Column(type="array", nullable=false)
     *
     */
    private $methods = [];


    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private $path;


    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private $controller;

    /**
     * @var CmsPage
     *
     * @ORM\OneToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsPage", mappedBy="route", cascade={"remove"})
     */
    private $page;


    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     *
     */
    private $defaults;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     *
     */
    private $requirements;

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string) $this->getName();
    }

    public function isDynamic()
    {
        return (bool) preg_match('/\{.*\}/', $this->getPath());
    }

    public function getParams()
    {
        preg_match_all('/\{(\w+)\}/', $this->getPath(), $matches);
        return $matches[1];
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
