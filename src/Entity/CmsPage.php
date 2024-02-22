<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JetBrains\PhpStorm\ArrayShape;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use WebEtDesign\CmsBundle\Handler\CmsPageSlugHandler;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;
use WebEtDesign\SeoBundle\Entity\SeoAwareTrait;
use WebEtDesign\SeoBundle\Entity\SmoOpenGraphTrait;
use WebEtDesign\SeoBundle\Entity\SmoTwitterTrait;

#[Gedmo\Tree(type: 'nested')]
#[ORM\Entity(repositoryClass: CmsPageRepository::class)]
#[ORM\Table(name: 'cms__page')]
#[Gedmo\Loggable(logEntryClass: CmsLogEntry::class)]
class CmsPage implements Loggable
{
    use SeoAwareTrait;
    use SmoOpenGraphTrait;
    use SmoTwitterTrait;
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Gedmo\Versioned]
    private string $title = '';

    /**
     * @var string | null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $template = '';

    /**
     *
     * @var ArrayCollection|PersistentCollection
     *
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsContent", mappedBy="page", cascade={"persist", "remove"})
     * @ORM\OrderBy({"position" = "ASC"})
     */
    #[ORM\OneToMany(mappedBy: 'page', targetEntity: CmsContent::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private PersistentCollection|ArrayCollection $contents;

    /**
     * @var null | CmsRouteInterface
     *
     * @ORM\OneToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsRoute", inversedBy="page", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="route_id", referencedColumnName="id", onDelete="CASCADE"))
     */
    #[ORM\OneToOne(targetEntity: CmsRoute::class, inversedBy: 'page', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'route_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?CmsRouteInterface $route = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Slug(handlers={
     *      @Gedmo\SlugHandler(class="WebEtDesign\CmsBundle\Handler\CmsPageSlugHandler")
     * }, fields={"title"}, unique=false)
     *
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Gedmo\Slug(fields: ['title'], unique: false)]
    #[Gedmo\SlugHandler(class: CmsPageSlugHandler::class)]
    #[Gedmo\Versioned]
    private ?string $slug = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $breadcrumb = null;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => 0])]
    #[Gedmo\Versioned]
    private bool $active = false;

    /**
     * @var array
     *
     * @ORM\Column(type="array", nullable=true)
     */
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    #[Gedmo\Versioned]
    private array $roles;

    /** @var Collection
     * @ORM\ManyToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsPage")
     * @ORM\JoinTable(name="cms__page_has_page",
     *      joinColumns={@ORM\JoinColumn(name="page_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="associated_page_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    #[ORM\ManyToMany(targetEntity: CmsPage::class)]
    #[ORM\JoinTable(name: 'cms__page_has_page')]
    #[ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'associated_page_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $crossSitePages;

    private mixed $referencePage;

    /**
     * @var ArrayCollection|Collection
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsPageDeclination", mappedBy="page", cascade={"persist", "remove"})
     */
    #[ORM\OneToMany(targetEntity: CmsPageDeclination::class, mappedBy: 'page', cascade: ['persist', 'remove'])]
    private Collection|ArrayCollection $declinations;

    /**
     * @var ArrayCollection|Collection
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsMenuItem", mappedBy="page")
     */
    #[ORM\OneToMany(targetEntity: CmsMenuItem::class, mappedBy: 'page')]
    private Collection|ArrayCollection $menuItems;

    /**
     * @var CmsSite|null
     *
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsSite", inversedBy="pages")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: CmsSite::class, inversedBy: 'pages')]
    #[ORM\JoinColumn(name: 'site_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\Versioned]
    private ?CmsSite $site = null;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    #[Gedmo\TreeLeft]
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    private ?int $lft = null;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    private ?int $lvl = null;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    #[Gedmo\TreeRight]
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    private ?int $rgt = null;

    /**
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="CmsPage")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: 'CmsPage')]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?CmsPage $root = null;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="CmsPage", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: 'CmsPage', inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    private ?CmsPage $parent = null;

    /**
     * @var ArrayCollection|Collection
     * @ORM\OneToMany(targetEntity="CmsPage", mappedBy="parent", cascade={"remove"})
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: 'CmsPage', cascade: ['remove'])]
    private Collection|ArrayCollection $children;

    #[ORM\Column(options: ['default' => false])]
    private bool $noIndex = false;

    private ?string $moveMode = null;

    private mixed $moveTarget = null;

    public bool $rootPage = false;

    /**
     * Set at true tu disable the creation of contents in listener, used in page import context
     * @var bool
     */
    public bool $dontImportContent = false;

    /**
     * Set at false tu disable the creation of route in listener
     * @var bool
     */
    public bool $initRoute = true;

    public $indexedContent = null;

    #[Gedmo\Versioned]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['default' => UrlConcrete::CHANGEFREQ_MONTHLY])]
    protected ?string $seoSitemapChangeFreq = UrlConcrete::CHANGEFREQ_MONTHLY;

    public function setPosition($values)
    {
        $this->setMoveMode($values['moveMode']);
        $this->setMoveTarget($values['moveTarget']);
    }

    #[ArrayShape(['moveMode' => 'mixed|null|string', 'moveTarget' => 'mixed|null'])] public function getPosition()
    {
        return [
            'moveMode' => $this->getMoveMode(),
            'moveTarget' => $this->getMoveTarget()
        ];
    }

    public function getChildrenRight()
    {
        $criteria = Criteria::create()->orderBy(['rgt' => 'ASC']);

        return $this->children->matching($criteria);
    }

    public function getChildrenLeft()
    {
        $criteria = Criteria::create()->orderBy(['lft' => 'ASC']);

        return $this->children->matching($criteria);
    }

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->contents = new ArrayCollection();
        $this->declinations = new ArrayCollection();
        $this->setActive(false);
        $this->roles          = [];
        $this->crossSitePages = new ArrayCollection();
        $this->menuItems = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string)$this->getTitle();
    }

    public function isRoot()
    {
        return $this->getId() == $this->getRoot()->getId();
    }

    public function isHybrid()
    {
        if ($this->getRoute()) {
            return !empty($this->getRoute()->getController());
        }

        return false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getSeoSitemapPriority(): float
    {
        return $this->seoSitemapPriority ?: 1 - $this->getLvl() * 0.2;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return CmsRouteInterface|null
     */
    public function getRoute(): ?CmsRouteInterface
    {
        return $this->route;
    }

    /**
     * @param null|CmsRouteInterface $route
     */
    public function setRoute(?CmsRouteInterface $route): void
    {
        $this->route = $route;
    }

    /**
     * @return ArrayCollection
     */
    public function getContents()
    {
        return $this->contents;
    }

    public function getContent($code): ?CmsContent
    {
        $criteria = new Criteria();
        $criteria
            ->where(
                Criteria::expr()->eq('code', $code)
            );

        return $this->contents->matching($criteria)->first() ?: null;
    }

    /**
     * @param ArrayCollection $contents
     */
    public function setContents(ArrayCollection $contents): void
    {
        $this->contents = $contents;
    }

    public function addContent(CmsContent $content): self
    {
        if (!$this->contents->contains($content)) {
            $this->contents[] = $content;
            $content->setPage($this);
        }

        return $this;
    }

    public function removeContent(CmsContent $content): self
    {
        if ($this->contents->contains($content)) {
            $this->contents->removeElement($content);
            // set the owning side to null (unless already changed)
            if ($content->getPage() === $this) {
                $content->setPage(null);
            }
        }

        return $this;
    }

    /**
     * @return null|string
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param null|string $template
     */
    public function setTemplate(?string $template): void
    {
        $this->template = $template;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @return array|null
     */
    public function getRoles(): ?array
    {
        return $this->roles;
    }

    /**
     * @param array|null $roles
     * @return CmsPage
     */
    public function setRoles(?array $roles): CmsPage
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @param ArrayCollection $crossSitePages
     * @return CmsPage
     */
    public function setCrossSitePages(Collection $crossSitePages): self
    {
        /** @var CmsPage $crossSitePage */
        foreach ($crossSitePages as $crossSitePage) {
            $this->addCrossSitePage($crossSitePage);
        }

        return $this;
    }

    /**
     * @return Collection|CmsPage[]
     */
    public function getCrossSitePages(): Collection
    {
        return $this->crossSitePages;
    }

    public function getCrossSitePage(CmsSite $cmsSite): ?CmsPage
    {
        foreach ($this->getCrossSitePages() as $crossSitePage) {
            if($crossSitePage->getSite()->getId() === $cmsSite->getId()){
                return $crossSitePage;
            }
        }
        return null;
    }

    public function addCrossSitePage(CmsPage $crossSitePage): self
    {
        if (!$this->crossSitePages->contains($crossSitePage) && $crossSitePage != $this) {
            $this->crossSitePages[] = $crossSitePage;
            $crossSitePage->addCrossSitePage($this);
            $crossSitePage->setCrossSitePages($this->crossSitePages);
        }

        return $this;
    }

    public function removeCrossSitePage(CmsPage $crossSitePage): self
    {
        if ($this->crossSitePages->contains($crossSitePage)) {
            $this->crossSitePages->removeElement($crossSitePage);
            $crossSitePage->removeCrossSitePageBis($this);
            /** @var CmsPage $v */
            foreach ($this->crossSitePages as $v) {
//                $crossSitePage->removeCrossSitePageBis($v);
                $v->removeCrossSitePage($crossSitePage);
            }
        }

        return $this;
    }

    public function removeCrossSitePageBis(CmsPage $crossSitePage)
    {
        if ($this->crossSitePages->contains($crossSitePage)) {
            $this->crossSitePages->removeElement($crossSitePage);
        }
    }

    /**
     * @return mixed
     */
    public function getReferencePage()
    {
        return $this->referencePage;
    }

    /**
     * @param mixed $referencePage
     */
    public function setReferencePage($referencePage): void
    {
        $this->referencePage = $referencePage;
    }

    /**
     * @return ArrayCollection
     */
    public function getDeclinations()
    {
        return $this->declinations;
    }

    /**
     * @param ArrayCollection $declinations
     */
    public function setDeclinations(ArrayCollection $declinations): void
    {
        $this->declinations = $declinations;
    }

    public function addDeclination($declination): self
    {
        if (!$this->declinations->contains($declination)) {
            $this->declinations[] = $declination;
            $declination->setPage($this);
        }

        return $this;
    }

    public function removeDeclination($declination): self
    {
        if ($this->declinations->contains($declination)) {
            $this->declinations->removeElement($declination);
            // set the owning side to null (unless already changed)
            if ($declination->getPage() === $this) {
                $declination->setPage(null);
            }
        }

        return $this;
    }

    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param CmsSite $site
     */
    public function setSite($site): void
    {
        $this->site = $site;
    }

    /**
     * @return mixed
     */
    public function getMoveMode()
    {
        return $this->moveMode;
    }

    /**
     * @param mixed $moveMode
     */
    public function setMoveMode($moveMode): void
    {
        $this->moveMode = $moveMode;
    }

    /**
     * @return mixed
     */
    public function getMoveTarget()
    {
        return $this->moveTarget;
    }

    /**
     * @param mixed $moveTarget
     */
    public function setMoveTarget($moveTarget): void
    {
        $this->moveTarget = $moveTarget;
    }

    /**
     * @return mixed
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * @param mixed $lvl
     */
    public function setLvl($lvl): void
    {
        $this->lvl = $lvl;
    }

    /**
     * @return mixed
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * @param mixed $rgt
     */
    public function setRgt($rgt): void
    {
        $this->rgt = $rgt;
    }

    /**
     * @return CmsPage|null
     */
    public function getRoot(): ?CmsPage
    {
        return $this->root;
    }

    /**
     * @param CmsPage|null $root
     */
    public function setRoot(?CmsPage $root): void
    {
        $this->root = $root;
    }

    /**
     * @return CmsPage|null
     */
    public function getParent(): ?CmsPage
    {
        return $this->parent;
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

    /**
     * @param CmsPage|null $parent
     */
    public function setParent(?CmsPage $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(CmsPage $page): self
    {
        if (!$this->children->contains($page)) {
            $this->children[] = $page;
            $page->setParent($this);
        }

        return $this;
    }

    public function removeChild(CmsPage $page): self
    {
        if ($this->children->contains($page)) {
            $this->children->removeElement($page);
            // set the owning side to null (unless already changed)
            if ($page->getParent() === $this) {
                $page->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * @param mixed $lft
     */
    public function setLft($lft): void
    {
        $this->lft = $lft;
    }

    /**
     * @return null|Collection
     */
    public function getMenuItems()
    {
        return $this->menuItems;
    }

    /**
     * @param ArrayCollection $menuItems
     */
    public function setMenuItems(ArrayCollection $menuItems): void
    {
        $this->menuItems = $menuItems;
    }

    public function addMenuItem(CmsMenuItem $menuItem): self
    {
        if (!$this->menuItems->contains($menuItem)) {
            $this->menuItems[] = $menuItem;
            $menuItem->setPage($this);
        }

        return $this;
    }

    public function removeMenuItem(CmsMenuItem $menuItem): self
    {
        if ($this->menuItems->contains($menuItem)) {
            $this->menuItems->removeElement($menuItem);
            // set the owning side to null (unless already changed)
            if ($menuItem->getPage() === $this) {
                $menuItem->setPage(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBreadcrumb(): ?string
    {
        return $this->breadcrumb;
    }

    /**
     * @param string|null $breadcrumb
     */
    public function setBreadcrumb(?string $breadcrumb): void
    {
        $this->breadcrumb = $breadcrumb;
    }

    /**
     * @return bool
     */
    public function isNoIndex(): bool
    {
        return $this->noIndex;
    }

    /**
     * @param bool $noIndex
     * @return CmsPage
     */
    public function setNoIndex(bool $noIndex): CmsPage
    {
        $this->noIndex = $noIndex;

        return $this;
    }

}
