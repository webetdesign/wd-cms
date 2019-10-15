<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use WebEtDesign\CmsBundle\Utils\SmoFacebookTrait;
use WebEtDesign\CmsBundle\Utils\SmoTwitterTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;


/**
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
     */
    private $contents;

    /**
     * @var null | CmsRouteInterface
     *
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

    private $site;

    private $referencePage;

    private $declinations;

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
}
