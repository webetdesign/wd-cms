<?php

namespace WebEtDesign\CmsBundle\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use stdClass;

/**
 * @ORM\Entity(repositoryClass="WebEtDesign\CmsBundle\Repository\CmsMenuItemRepository")
 * @ORM\Table(name="cms__menu_item")
 * @Gedmo\Tree(type="nested")
 */
class CmsMenuItem
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
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true, name="link_type")
     *
     */
    private $linkType;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true, name="link_value")
     *
     */
    private $linkValue;

    /**
     * @var CmsPage|null
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsPage")
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $page;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, name="is_visible", options={"default": true})
     */
    private $isVisible = true;

    /**
     * @var int
     * @Gedmo\TreeLevel
     * @ORM\Column(type="integer", nullable=false)
     *
     */
    private $lvl;

    /**
     * @var int
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer", nullable=false)
     *
     */
    private $lft;

    /**
     * @var int
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer", nullable=false)
     *
     */
    private $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsMenuItem")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsMenuItem", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @var CmsMenuItem[]|Collection|Selectable
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsMenuItem", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     *
     */
    private $classes;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $connected;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     *
     */
    private $role;

    /**
     * @var null|String
     */
    private $moveMode;

    /**
     * @var null|CmsMenuItem
     */
    private $moveTarget;

    /**
     * @var null|string
     * @ORM\Column(type="text", nullable=true)
     */
    private $params;

    /**
     * @var CmsMenu
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsMenu", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="menu_id", referencedColumnName="id")
     */
    private $menu;

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

    public function isRoot()
    {
        return $this->getId() == $this->getRoot()->getId();
    }

    public function getParentAtLvl($lvl)
    {
        if ($this->getLvl() < $lvl) {
            return null;
        }

        if ($this->getLvl() === $lvl) {
            return $this;
        } else {
            return $this->getParent()->getParentAtLvl($lvl);
        }
    }

    public function setPosition($values)
    {
        $this->setMoveMode($values['moveMode']);
        $this->setMoveTarget($values['moveTarget']);
    }

    public function getPosition()
    {
        return [
            'moveMode' => $this->getMoveMode(),
            'moveTarget' => $this->getMoveTarget()
        ];
    }

    public function getVisibleString()
    {
        if ($this->isVisible()) {
            switch ($this->getConnected()) {
                case 'ONLY_LOGIN':
                    return 'Visible si connecté';
                    break;
                case 'ONLY_LOGOUT':
                    return 'Visible si non connecté';
                    break;
                default:
                    return 'Visible';
                    break;
            }
        } else {
            return 'Caché';
        }
    }

    public function getChildrenRight()
    {
        $criteria = Criteria::create()->orderBy(['rgt'=>'ASC']);

        return $this->children->matching($criteria);
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
     * @return Collection|CmsMenuItem[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(CmsMenuItem $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(CmsMenuItem $child): self
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
     * @return CmsMenuItem|null
     */
    public function getMoveTarget(): ?CmsMenuItem
    {
        return $this->moveTarget;
    }

    /**
     * @param CmsMenuItem|null $moveTarget
     */
    public function setMoveTarget(?CmsMenuItem $moveTarget): void
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
     * @return CmsMenuItem
     */
    public function setClasses(?string $classes): CmsMenuItem
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
     * @return CmsMenuItem
     */
    public function setConnected(?string $connected): CmsMenuItem
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
     * @return CmsMenuItem
     */
    public function setRole(?string $role): CmsMenuItem
    {
        $this->role = $role;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSite()
    {
        $this->getMenu()->getSite();
    }

    public function getSlug(){
        $slugify = new Slugify();
        return $slugify->slugify($this->getName(), "_");
    }

    /**
     * @param string|null $params
     * @return CmsMenuItem
     */
    public function setParams(?string $params): CmsMenuItem
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getParams(): ?string
    {
        return $this->params;
    }

    /**
     * @param bool $isVisible
     * @return CmsMenuItem
     */
    public function setIsVisible(bool $isVisible): self
    {
        $this->isVisible = $isVisible;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    /**
     * @param CmsMenu $menu
     * @return CmsMenuItem
     */
    public function setMenu(CmsMenu $menu): CmsMenuItem
    {
        $this->menu = $menu;
        return $this;
    }

    /**
     * @return CmsMenu
     */
    public function getMenu(): ?CmsMenu
    {
        return $this->menu;
    }


}