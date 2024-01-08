<?php
declare(strict_types=1);
/** @noinspection RegExpRedundantEscape */

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Gedmo\Loggable\Loggable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use WebEtDesign\CmsBundle\Repository\CmsRouteRepository;


#[UniqueEntity('path')]
#[ORM\Entity(repositoryClass: CmsRouteRepository::class)]
#[ORM\Table(name: 'cms__route')]
#[Gedmo\Loggable(logEntryClass: CmsLogEntry::class)]
abstract class AbstractCmsRoute implements CmsRouteInterface, Loggable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;


    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Gedmo\Versioned]
    private ?string $name = null;


    #[ORM\Column(type: Types::ARRAY, nullable: false)]
    #[Gedmo\Versioned]
    private array $methods = [];


    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Gedmo\Versioned]
    private ?string $path = null;


    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $controller = null;

    #[ORM\OneToOne(mappedBy: 'route', targetEntity: CmsPage::class, cascade: ["remove"])]
    #[Gedmo\Versioned]
    private ?CmsPage $page = null;


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $defaults = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $requirements = null;

    public function __toString()
    {
        return (string) $this->getName();
    }

    public function isDynamic(): bool
    {
        return (bool) preg_match('/\{.*\}/', $this->getPath());
    }

    public function getParams(): array
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
     * @return CmsPage|null
     */
    public function getPage(): ?CmsPage
    {
        return $this->page;
    }

    /**
     * @param CmsPage|null $page
     */
    public function setPage(?CmsPage $page): void
    {
        $this->page = $page;
    }

    /**
     * @return string|null
     */
    public function getController(): ?string
    {
        return $this->controller;
    }

    /**
     * @param string|null $controller
     */
    public function setController(?string $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * @return string|null
     */
    public function getDefaults(): ?string
    {
        return $this->defaults;
    }

    /**
     * @param string|null $defaults
     * @return self
     */
    public function setDefaults(?string $defaults): self
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequirements(): ?string
    {
        return $this->requirements;
    }

    /**
     * @param string|null $requirements
     * @return self
     */

    public function setRequirements(?string $requirements): self
    {
        $this->requirements = $requirements;
        return $this;
    }

}
