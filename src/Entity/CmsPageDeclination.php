<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping as ORM;
use WebEtDesign\SeoBundle\Entity\SmoOpenGraphTrait;
use WebEtDesign\SeoBundle\Entity\SmoTwitterTrait;


/**
 * @ORM\Entity(repositoryClass="WebEtDesign\CmsBundle\Repository\CmsPageDeclinationRepository")
 * @ORM\Table(name="cms__page_declination")
 */
class CmsPageDeclination
{
    use SeoAwareTrait;
    use SmoOpenGraphTrait;
    use SmoTwitterTrait;


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @var CmsPage|null
     *
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsPage", inversedBy="declinations")
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?CmsPage $page = null;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private string $title = '';

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private string $technic_name = '';

    /**
     * @var ?string
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private ?string $locale = null;

    /**
     * @var ArrayCollection|PersistentCollection
     *
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsContent", mappedBy="declination", cascade={"remove", "persist"})
     */
    private PersistentCollection|ArrayCollection $contents;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", length=255, nullable=false, options={"default": false})
     */
    private bool $active = false;

    /**
     * @var string
     * @ORM\Column(type="text", length=255, nullable=false)
     */
    private string $params = '[]';

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->contents = new ArrayCollection();
        $this->setActive(false);
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string)$this->getTitle();
    }

    public function getPath(): array|string|null
    {
        $params = json_decode($this->getParams(), true);
        $pagePath = $this->getPage()->getRoute()->getPath();
        $path     = preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($params) {
            return $params[$matches[1]] ?? '';
        }, $pagePath);
        return preg_replace('/\/*$/', '', $path);
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
     * @return ArrayCollection|PersistentCollection
     */
    public function getContents(): ArrayCollection|PersistentCollection
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
            $content->setDeclination($this);
        }

        return $this;
    }

    public function removeContent(CmsContent $content): self
    {
        if ($this->contents->contains($content)) {
            $this->contents->removeElement($content);
            // set the owning side to null (unless already changed)
            if ($content->getDeclination() === $this) {
                $content->setDeclination(null);
            }
        }

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

    /**
     * @param mixed $page
     * @return CmsPageDeclination
     */
    public function setPage($page): static
    {
        $this->page = $page;
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
     * @param string $params
     * @return CmsPageDeclination
     */
    public function setParams($params): static
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @return string
     */
    public function getParams(): string
    {
        return $this->params;
    }

    public function getSeoTitle(): ?string
    {
        if ($this->seo_title === null) {
            return '';
        }

        return $this->seo_title;
    }

    public function getTechnicName(): ?string
    {
        return $this->technic_name;
    }

    public function setTechnicName(?string $technic_name): self
    {
        $this->technic_name = $technic_name;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

}
