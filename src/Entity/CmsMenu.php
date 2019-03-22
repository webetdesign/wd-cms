<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 */
class CmsMenu
{
    private $id;

    /**
     * @var null|string
     *
     */
    private $name;

    /**
     * @var null|string
     *
     */
    private $code;

    /**
     * @var null|string
     */
    private $linkType;

    /**
     *
     */
    private $page;

    /**
     * @var null|string
     *
     */
    private $linkValue;

    /**
     */
    private $lft;

    /**
     */
    private $lvl;

    /**
     */
    private $rgt;

    /**
     */
    private $root;

    /**
     */
    private $parent;

    /**
     */
    private $children;

    /**
     * @var null|String
     */
    private $moveMode;

    /** @var string */
    private $classes;

    /** @var string */
    private $connected;
    
    /** @var string */
    private $role;

    /**
     * @var null|CmsMenu
     */
    private $moveTarget;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

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

    public function getLft(): ?int
    {
        return $this->lft;
    }

    public function setLft(int $lft): self
    {
        $this->lft = $lft;

        return $this;
    }

    public function getLvl(): ?int
    {
        return $this->lvl;
    }

    public function setLvl(int $lvl): self
    {
        $this->lvl = $lvl;

        return $this;
    }

    public function getRgt(): ?int
    {
        return $this->rgt;
    }

    public function setRgt(int $rgt): self
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getRoot(): ?self
    {
        return $this->root;
    }

    public function setRoot(?self $root): self
    {
        $this->root = $root;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|CmsMenu[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(CmsMenu $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(CmsMenu $child): self
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param null|string $code
     */
    public function setCode($code): void
    {
        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param mixed $page
     */
    public function setPage($page): void
    {
        $this->page = $page;
    }

    /**
     * @return null|String
     */
    public function getMoveMode(): ?String
    {
        return $this->moveMode;
    }

    /**
     * @param null|String $moveMode
     */
    public function setMoveMode(?String $moveMode): void
    {
        $this->moveMode = $moveMode;
    }

    /**
     * @return CmsMenu|null
     */
    public function getMoveTarget(): ?CmsMenu
    {
        return $this->moveTarget;
    }

    /**
     * @param CmsMenu|null $moveTarget
     */
    public function setMoveTarget(?CmsMenu $moveTarget): void
    {
        $this->moveTarget = $moveTarget;
    }

    /**
     * @return null|string
     */
    public function getLinkValue(): ?string
    {
        return $this->linkValue;
    }

    /**
     * @param null|string $linkValue
     */
    public function setLinkValue(?string $linkValue): void
    {
        $this->linkValue = $linkValue;
    }

    /**
     * @return string
     */
    public function getLinkType(): ?string
    {
        return $this->linkType;
    }

    /**
     * @param string $linkType
     */
    public function setLinkType(?string $linkType): void
    {
        $this->linkType = $linkType;
    }

    /**
     * @return string
     */
    public function getClasses(): ?string
    {
        return $this->classes;
    }

    /**
     * @param string $classes
     * @return CmsMenu
     */
    public function setClasses(?string $classes): CmsMenu
    {
        $this->classes = $classes;
        return $this;
    }

    /**
     * @return string
     */
    public function getConnected(): ?string
    {
        return $this->connected;
    }

    /**
     * @param string $connected
     * @return CmsMenu
     */
    public function setConnected(?string $connected): CmsMenu
    {
        $this->connected = $connected;
        return $this;
    }

    /**
     * @return array
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * @param string $roles
     * @return CmsMenu
     */
    public function setRole(?string $role): CmsMenu
    {
        $this->role = $role;
        return $this;
    }
}
