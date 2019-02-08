<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use WebEtDesign\CmsBundle\Model\SeoAwareTrait;

/**
 * @ORM\Table(name="cms__page")
 * @ORM\Entity(repositoryClass="App\Repository\CmsPageRepository")
 * @ORM\HasLifecycleCallbacks
 */
class CmsPage
{
    use SeoAwareTrait;

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @var null|string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $template;

    /**
     *
     * @var ArrayCollection|PersistentCollection
     *
     * @ORM\OneToMany(targetEntity="CmsContent", mappedBy="page", cascade={"persist", "remove"})
     */
    private $contents;

    /**
     * @var null | CmsRoute
     *
     * @ORM\OneToOne(targetEntity="CmsRoute", inversedBy="page", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="route_id", referencedColumnName="id")
     */
    private $route;

    /**
     * @var string
     *
     * Slug is only used to create CmsRoute path
     *
     * @Gedmo\Slug(fields={"title"}, updatable=true, separator="-")
     * @ORM\Column(type="string")
     */
    private $slug;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=false, options={"default": 0})
     */
    private $active;

    /**
     * @inheritDoc
     */
    public function __construct() {
        $this->contents = new ArrayCollection();
        $this->setActive(false);
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
     * @return CmsRoute|null
     */
    public function getRoute(): ?CmsRoute
    {
        return $this->route;
    }

    /**
     * @param CmsRoute|null $route
     */
    public function setRoute(?CmsRoute $route): void
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
}
