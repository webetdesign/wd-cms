<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;
use WebEtDesign\CmsBundle\Utils\SmoFacebookTrait;
use WebEtDesign\CmsBundle\Utils\SmoTwitterTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="WebEtDesign\CmsBundle\Repository\CmsPageRepository")
 * @ORM\Table(name="cms__page")
 */
class CmsPage
{
    use SeoAwareTrait;
    use SmoFacebookTrait;
    use SmoTwitterTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private $title;

    /**
     * @var string | null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $template;

    /**
     *
     * @var ArrayCollection|PersistentCollection
     *
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsContent", mappedBy="page", cascade={"persist", "remove"})
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $contents;

    /**
     * @var null | CmsRouteInterface
     *
     * Mapping Relation in WebEtDesignCmsExtension.php
     */
    private $route;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Slug(fields={"title"}, separator="-")
     *
     */
    private $slug;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private $class_association;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private $query_association;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    private $association;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $active;

    /**
     * @var array
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $roles;

    private $crossSitePages;

    private $referencePage;

    private $declinations;

    /**
     * @var Collection|null
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsMenuItem", mappedBy="page")
     */
    private $menuItems;

    /**
     * @var CmsSite
     *
     * Mapping Relation in WebEtDesignCmsExtension
     */
    private $site;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeRoot
     * Mapping Relation in WebEtDesignCmsExtension
     */
    private $root;

    /**
     * @Gedmo\TreeParent
     * Mapping Relation in WebEtDesignCmsExtension
     */
    private $parent;

    /**
     * Mapping Relation in WebEtDesignCmsExtension
     *
     * @var CmsPage[]|Collection
     */
    private $children;

    private $moveMode;

    private $moveTarget;

    public $rootPage = false;

    /**
     * Set at true tu disable the creation of contents in listener, used in page import context
     * @var bool
     */
    public $dontImportContent = false;

    public $indexedContent = null;

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

    public function getChildrenRight()
    {
        $criteria = Criteria::create()->orderBy(['rgt' => 'ASC']);

        return $this->children->matching($criteria);
    }

    /**
     * @return mixed
     */
    public function getClassAssociation()
    {
        return $this->class_association;
    }

    /**
     * @param mixed $class_association
     */
    public function setClassAssociation($class_association)
    {
        $this->class_association = $class_association;
    }

    /**
     * @return mixed
     */
    public function getQueryAssociation()
    {
        return $this->query_association;
    }

    /**
     * @param mixed $query_association
     */
    public function setQueryAssociation($query_association)
    {
        $this->query_association = $query_association;
    }

    /**
     * @return mixed
     */
    public function getAssociation()
    {
        return $this->association;
    }

    /**
     * @param mixed $association
     */
    public function setAssociation($association)
    {
        $this->association = $association;
    }

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->contents = new ArrayCollection();
        $this->setActive(false);
        $this->roles          = [];
        $this->crossSitePages = new ArrayCollection();
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

    public function setSlug(string $slug): self
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
     */
    public function setCrossSitePages(Collection $crossSitePages): self
    {
        dump('set');
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

}
