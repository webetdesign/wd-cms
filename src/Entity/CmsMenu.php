<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\PersistentCollection;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Loggable\Loggable;
use WebEtDesign\CmsBundle\Repository\CmsMenuRepository;

#[ORM\Entity(repositoryClass: CmsMenuRepository::class)]
#[ORM\Table(name: 'cms__menu')]
#[ORM\UniqueConstraint(name: 'code_idx', columns: ['code', 'site_id'])]
#[Gedmo\Loggable(logEntryClass: CmsLogEntry::class)]
class CmsMenu implements Loggable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Gedmo\Versioned]
    private ?string $label = null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false)]
    #[Gedmo\Versioned]
    private ?string $code = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $type = null;

    #[ORM\OneToMany(mappedBy: "menu", targetEntity: CmsMenuItem::class, cascade: ["persist", "remove"])]
    private Collection $children;

    #[ORM\ManyToOne(targetEntity: CmsSite::class, inversedBy: "menus")]
    #[ORM\JoinColumn(name: "site_id", referencedColumnName: "id")]
    #[Gedmo\Versioned]
    private ?CmsSite $site = null;

    public bool $initRoot = true;


    public function __construct()
    {
        $this->type = CmsMenuTypeEnum::DEFAULT;
        $this->children = new ArrayCollection();
    }

    public function __toString()
    {
        return (string)$this->getLabel();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @param string|null $label
     * @return CmsMenu
     */
    public function setLabel(?string $label): CmsMenu
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getRoot()
    {
        $criteria = new Criteria();
        $criteria->where(
            Criteria::expr()->eq('lvl', 0)
        );

        return $this->children->matching($criteria)->first();
    }

    /**
     * @return Collection
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChildren($children): self
    {
        if (!$this->children->contains($children)) {
            $this->children[] = $children;
            $children->setSite($this);
        }

        return $this;
    }

    public function removeChildren($children): self
    {
        if ($this->children->contains($children)) {
            $this->children->removeElement($children);
            // set the owning side to null (unless already changed)
            if ($children->getPage() === $this) {
                $children->setPage(null);
            }
        }

        return $this;
    }

    /**
     * @param string $code
     * @return CmsMenu
     */
    public function setCode(string $code): CmsMenu
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $type
     * @return CmsMenu
     */
    public function setType(string $type): CmsMenu
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param CmsSite $site
     * @return CmsMenu
     */
    public function setSite(CmsSite $site): CmsMenu
    {
        $this->site = $site;
        return $this;
    }

    /**
     * @return CmsSite|null
     */
    public function getSite(): ?CmsSite
    {
        return $this->site;
    }

    public function addChild(CmsMenuItem $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setMenu($this);
        }

        return $this;
    }

    public function removeChild(CmsMenuItem $child): self
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
            // set the owning side to null (unless already changed)
            if ($child->getMenu() === $this) {
                $child->setMenu(null);
            }
        }

        return $this;
    }

}
