<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use WebEtDesign\CmsBundle\Utils\SmoFacebookTrait;
use WebEtDesign\CmsBundle\Utils\SmoTwitterTrait;

/**
 */
class CmsPage
{
    use SeoAwareTrait;
    use SmoFacebookTrait;
    use SmoTwitterTrait;


    /**
     * @var int
     *
     */
    private $id;

    /**
     * @var string
     *
     */
    private $title;

    /**
     * @var null|string
     *
     */
    private $template;

    /**
     *
     * @var ArrayCollection|PersistentCollection
     *
     */
    private $contents;

    /**
     * @var null | CmsRouteInterface
     *
     */
    private $route;

    /**
     * @var string
     *
     * Slug is only used to create CmsRoute path
     *
     */
    private $slug;

    /**
     * @var string
     *
     */
    private $class_association;

    /**
     * @var string
     *
     */
    private $query_association;

    /**
     * @var int
     *
     */
    private $association;

    /**
     * @var boolean
     *
     */
    private $active;

    /**
     * @var null|array
     */
    private $roles;

    private $crossSitePages;

    private $site;

    private $referencePage;


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
    public function __construct() {
        $this->contents = new ArrayCollection();
        $this->setActive(false);
        $this->roles = [];
        $this->crossSitePages = new ArrayCollection();
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string) $this->getTitle();
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

    public function getSite()
    {
        return $this->site;
    }

    public function setSite($site): self
    {
        $this->site = $site;

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
        if (!$this->crossSitePages->contains($crossSitePage)) {
            $this->crossSitePages[] = $crossSitePage;
        }

        return $this;
    }

    public function removeCrossSitePage(CmsPage $crossSitePage): self
    {
        if ($this->crossSitePages->contains($crossSitePage)) {
            $this->crossSitePages->removeElement($crossSitePage);
        }

        return $this;
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
}
