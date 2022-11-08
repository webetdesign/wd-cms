<?php

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
use WebEtDesign\CmsBundle\Repository\CmsMenuRepository;

/**
 * @ORM\Entity(repositoryClass="WebEtDesign\CmsBundle\Repository\CmsMenuRepository")
 * @ORM\Table(name="cms__menu", uniqueConstraints={@ORM\UniqueConstraint(name="code_idx", columns={"code", "site_id"})})
 */
#[ORM\Entity(repositoryClass: CmsMenuRepository::class)]
#[ORM\Table(name: "cms__menu")]
#[ORM\UniqueConstraint(name: "code_idx", columns: ["code", "site_id"])]
class CmsMenu
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @var string
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private $label;

    /**
     * @ORM\Column(type="string", length=128, nullable=false)
     *
     * @var string
     */
    #[ORM\Column(type: Types::STRING, length: 128, nullable: false)]
    private $code;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private $type;

    /**
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsMenuItem", mappedBy="menu", cascade={"persist", "remove"})
     * @var CmsMenuItem[]|Collection|Selectable
     */
    #[ORM\OneToMany(mappedBy: "menu", targetEntity: CmsMenuItem::class, cascade: ["persist", "remove"])]
    private $children;

    /**
     * @var CmsSite
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsSite", inversedBy="menus")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="id")
     */
    #[ORM\ManyToOne(targetEntity: CmsSite::class, inversedBy: "menus")]
    #[ORM\JoinColumn(name: "site_id", referencedColumnName: "id")]
    private $site;

    public $initRoot = true;


    public function __construct()
    {
        $this->type = CmsMenuTypeEnum::DEFAULT;
        $this->children = new ArrayCollection();
    }

    /**
     * @inheritDoc
     */
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
     * @param int $id
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
     * @return Collection|Selectable|CmsMenuItem[]
     */
    public function getChildren()
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
     * @return string
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
     * @return CmsSite
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
