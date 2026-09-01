<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;
use JetBrains\PhpStorm\ArrayShape;
use WebEtDesign\CmsBundle\Repository\CmsMenuItemRepository;

#[ORM\Entity(repositoryClass: CmsMenuItemRepository::class)]
#[ORM\Table(name: 'cms__menu_item')]
#[Gedmo\Tree(type: 'nested')]
#[Gedmo\Loggable(logEntryClass: CmsLogEntry::class)]
class CmsMenuItem implements Loggable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Gedmo\Versioned]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $information = null;

    #[ORM\Column(name: 'link_type', type: Types::STRING, length: 255, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $linkType = null;

    #[ORM\Column(name: 'link_value', type: Types::STRING, length: 255, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $linkValue = null;

    #[ORM\ManyToOne(targetEntity: CmsPage::class, inversedBy: 'menuItems')]
    #[ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[Gedmo\Versioned]
    private ?CmsPage $page = null;

    #[ORM\Column(name: 'is_visible', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    #[Gedmo\Versioned]
    private bool $isVisible = true;

    #[Gedmo\TreeLevel]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[Gedmo\Versioned]
    private ?int $lvl = null;

    #[Gedmo\TreeLeft]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[Gedmo\Versioned]
    private ?int $lft = null;

    #[Gedmo\TreeRight]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[Gedmo\Versioned]
    private ?int $rgt = null;

    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: CmsMenuItem::class)]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: "id", onDelete: 'CASCADE')]
    #[Gedmo\Versioned]
    private ?CmsMenuItem $root = null;

    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: CmsMenuItem::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\Versioned]
    private ?CmsMenuItem $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: CmsMenuItem::class)]
    #[ORM\OrderBy(['lft' => 'ASC'])]
    private ?Collection $children;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $liClass = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $ulClass = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $linkClass = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $iconClass = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $connected = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $role = null;

    private $moveMode;

    private $moveTarget;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $params = [];

    #[ORM\ManyToOne(targetEntity: CmsMenu::class, cascade: ["persist"], inversedBy: "children")]
    #[ORM\JoinColumn(name: "menu_id", referencedColumnName: "id")]
    private ?CmsMenu $menu = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $blank = false;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $anchor = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function __toString()
    {
        return (string)$this->getName();
    }

    public function getPath(): array|string|null
    {
        $pagePath = $this->getPage()->getRoute()->getPath();

        return preg_replace_callback('/\{(\w+)\}/', function ($matches) {
            return $this->params[$matches[1]] ?? '';
        }, $pagePath);
    }

    public function isRoot(): bool
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

    public function setPosition($values): void
    {
        $this->setMoveMode($values['moveMode']);
        $this->setMoveTarget($values['moveTarget']);
    }

    #[ArrayShape(['moveMode'   => "null|String",
                  'moveTarget' => "null|\WebEtDesign\CmsBundle\Entity\CmsMenuItem"
    ])] public function getPosition(): array
    {
        return [
            'moveMode'   => $this->getMoveMode(),
            'moveTarget' => $this->getMoveTarget()
        ];
    }

    public function getVisibleString(): string
    {
        if ($this->isVisible()) {
            return match ($this->getConnected()) {
                'ONLY_LOGIN'  => 'Visible si connecté',
                'ONLY_LOGOUT' => 'Visible si non connecté',
                default       => 'Visible',
            };
        } else {
            return 'Caché';
        }
    }

    public function getChildrenRight(): Collection&Selectable
    {
        $criteria = Criteria::create()->orderBy(['rgt' => 'ASC']);

        return $this->children->matching($criteria);
    }

    public function getChildrenLeft(): Collection&Selectable
    {
        $criteria = Criteria::create()->orderBy(['lft' => 'ASC']);

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
     * @return Collection
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
     * @return string|null
     */
    public function getInformation(): ?string
    {
        return $this->information;
    }

    /**
     * @param string|null $information
     * @return CmsMenuItem
     */
    public function setInformation(?string $information): self
    {
        $this->information = $information;

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
     * @return null|String
     */
    public function getMoveMode(): ?string
    {
        return $this->moveMode;
    }

    /**
     * @param null|String $moveMode
     */
    public function setMoveMode(?string $moveMode): void
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
     * @return string|null
     */
    public function getLinkType(): ?string
    {
        return $this->linkType;
    }

    /**
     * @param string|null $linkType
     */
    public function setLinkType(?string $linkType): void
    {
        $this->linkType = $linkType;
    }

    /**
     * @return string|null
     * @deprecated use getLiClass()
     */
    public function getClasses(): ?string
    {
        return $this->liClass;
    }

    /**
     * @return string|null
     */
    public function getConnected(): ?string
    {
        return $this->connected;
    }

    /**
     * @param string|null $connected
     * @return CmsMenuItem
     */
    public function setConnected(?string $connected): CmsMenuItem
    {
        $this->connected = $connected;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * @param string|null $role
     * @return CmsMenuItem
     */
    public function setRole(?string $role): CmsMenuItem
    {
        $this->role = $role;

        return $this;
    }

    public function getSite(): CmsSite
    {
        return $this->getMenu()->getSite();
    }

    public function getSlug(): string
    {
        $slugify = new Slugify();

        return $slugify->slugify($this->getName(), "_");
    }

    /**
     * @param array|null $params
     * @return CmsMenuItem
     */
    public function setParams(?array $params): CmsMenuItem
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params ?? [];
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
     * @param CmsMenu|null $menu
     * @return CmsMenuItem
     */
    public function setMenu(?CmsMenu $menu): CmsMenuItem
    {
        $this->menu = $menu;

        return $this;
    }

    /**
     * @return CmsMenu|null
     */
    public function getMenu(): ?CmsMenu
    {
        return $this->menu;
    }

    /**
     * @param bool $blank
     * @return CmsMenuItem
     */
    public function setBlank(bool $blank): CmsMenuItem
    {
        $this->blank = $blank;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBlank(): bool
    {
        return $this->blank;
    }

    /**
     * @param string|null $anchor
     * @return CmsMenuItem
     */
    public function setAnchor(?string $anchor): CmsMenuItem
    {
        $this->anchor = $anchor;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAnchor(): ?string
    {
        return $this->anchor;
    }

    public function getIsVisible(): ?bool
    {
        return $this->isVisible;
    }

    public function getBlank(): ?bool
    {
        return $this->blank;
    }

    /**
     * @param string|null $liClass
     * @return CmsMenuItem
     */
    public function setLiClass(?string $liClass): CmsMenuItem
    {
        $this->liClass = $liClass;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLiClass(): ?string
    {
        return $this->liClass;
    }

    /**
     * @param string|null $ulClass
     * @return CmsMenuItem
     */
    public function setUlClass(?string $ulClass): CmsMenuItem
    {
        $this->ulClass = $ulClass;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUlClass(): ?string
    {
        return $this->ulClass;
    }

    /**
     * @param string|null $linkClass
     * @return CmsMenuItem
     */
    public function setLinkClass(?string $linkClass): CmsMenuItem
    {
        $this->linkClass = $linkClass;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLinkClass(): ?string
    {
        return $this->linkClass;
    }

    /**
     * @return string|null
     */
    public function getIconClass(): ?string
    {
        return $this->iconClass;
    }

    /**
     * @param string|null $iconClass
     * @return CmsMenuItem
     */
    public function setIconClass(?string $iconClass): CmsMenuItem
    {
        $this->iconClass = $iconClass;

        return $this;
    }
}
