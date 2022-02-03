<?php
/**
 * Created by PhpStorm.
 * User: Leo MEYER
 * Date: 07/08/2019
 * Time: 15:24
 */

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;


/**
 * @ORM\Entity(repositoryClass="WebEtDesign\CmsBundle\Repository\CmsSiteRepository")
 * @ORM\Table(name="cms__site")
 */
class CmsSite
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @var int|null
     */
    private ?int $id = null;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(fields={"label", "locale"})
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private ?string $slug = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @var string|null
     */
    private ?string $label = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string|null
     */
    private ?string $locale = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string|null
     */
    private ?string $host = null;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     *
     * @var boolean
     */
    private bool $hostMultilingual = false;

    /**
     * @ORM\Column(name="`default`", type="boolean", options={"default" : 0})
     *
     * @var boolean
     */
    private bool $default;

    /**
     * @var CmsPage[]|Collection|Selectable
     *
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsPage", mappedBy="site", cascade={"persist", "remove"})
     */
    private Collection $pages;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string|null
     */
    private ?string $flagIcon = null;

    /**
     * @var CmsPage[]|Collection|Selectable
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsMenu", mappedBy="site", cascade={"persist", "remove"})
     */
    private Collection $menus;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsSharedBlock", mappedBy="site", cascade={"persist", "remove"})
     */
    private Collection $sharedBlocks;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private bool $visible = true;

    /**
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $templateFilter = null;

    public bool $initPage = true;
    public bool $initMenu = true;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
        $this->menus = new ArrayCollection();
        $this->sharedBlocks = new ArrayCollection();
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string)$this->getLabel() . (!empty($this->getLocale()) ? ' - ' . $this->getLocale() : '');
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
     * @return string
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(?string $host): void
    {
        $this->host = $host;
    }

    /**
     * @param string|null $label
     * @return CmsSite
     */
    public function setLabel(?string $label): CmsSite
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

    /**
     * @param bool $hostMultilingual
     */
    public function setHostMultilingual(bool $hostMultilingual)
    {
        $this->hostMultilingual = $hostMultilingual;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHostMultilingual(): ?bool
    {
        return $this->hostMultilingual;
    }

    /**
     * @param bool $default
     */
    public function setDefault(bool $default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault(): ?bool
    {
        return $this->default;
    }

    /**
     * @return string
     */
    public function getFlagIcon(): ?string
    {
        return $this->flagIcon;
    }

    /**
     * @param string $flagIcon
     */
    public function setFlagIcon(?string $flagIcon): void
    {
        $this->flagIcon = $flagIcon;
    }

    public function getMenuArbo()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('type', CmsMenuTypeEnum::PAGE_ARBO));

        $menus = $this->menus->matching($criteria);

        return $menus[0] ?? null;
    }

    public function getMenu()
    {
        return $this->menus;
    }

    public function addMenu($menu): self
    {
        if (!$this->menus->contains($menu)) {
            $this->menus[] = $menu;
            $menu->setSite($this);
        }

        return $this;
    }

    public function removeMenu($menu): self
    {
        if ($this->menus->contains($menu)) {
            $this->menus->removeElement($menu);
            // set the owning side to null (unless already changed)
            if ($menu->getSite() === $this) {
                $menu->setSite(null);
            }
        }

        return $this;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function getRootPage(): ?CmsPage
    {
        $criteria = new Criteria();
        $criteria->where(
            Criteria::expr()->eq('lvl', 0)
        );

        return $this->pages->matching($criteria)->first();
    }

    public function addPage($page): self
    {
        if (!$this->pages->contains($page)) {
            $this->pages[] = $page;
            $page->setSite($this);
        }

        return $this;
    }

    public function removePage($page): self
    {
        if ($this->pages->contains($page)) {
            $this->pages->removeElement($page);
            // set the owning side to null (unless already changed)
            if ($page->getSite() === $this) {
                $page->setSite(null);
            }
        }

        return $this;
    }

    /**
     * @param mixed $templateFilter
     * @return CmsSite
     */
    public function setTemplateFilter($templateFilter)
    {
        $this->templateFilter = $templateFilter;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTemplateFilter()
    {
        return $this->templateFilter;
    }

    /**
     * @param mixed $sharedBlocks
     * @return CmsSite
     */
    public function setSharedBlocks($sharedBlocks)
    {
        $this->sharedBlocks = $sharedBlocks;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSharedBlocks()
    {
        return $this->sharedBlocks;
    }

    public function addSharedBlock($sharedBlock): self
    {
        if (!$this->sharedBlocks->contains($sharedBlock)) {
            $this->sharedBlocks[] = $sharedBlock;
            $sharedBlock->setSite($this);
        }

        return $this;
    }

    public function removeSharedBlock($sharedBlock): self
    {
        if ($this->sharedBlocks->contains($sharedBlock)) {
            $this->sharedBlocks->removeElement($sharedBlock);
            // set the owning side to null (unless already changed)
            if ($sharedBlock->getPage() === $this) {
                $sharedBlock->setPage(null);
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @param bool $visible
     * @return CmsSite
     */
    public function setVisible(bool $visible): CmsSite
    {
        $this->visible = $visible;
        return $this;
    }

    public function getHostMultilingual(): ?bool
    {
        return $this->hostMultilingual;
    }

    public function getDefault(): ?bool
    {
        return $this->default;
    }

    public function getVisible(): ?bool
    {
        return $this->visible;
    }

    /**
     * @return Collection|CmsMenu[]
     */
    public function getMenus(): Collection
    {
        return $this->menus;
    }

    /**
     * @param string|null $slug
     * @return CmsSite
     */
    public function setSlug(?string $slug): CmsSite
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

}
