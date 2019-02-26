<?php

namespace WebEtDesign\CmsBundle\Entity;
use App\Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


/**
 */
class CmsContent
{
    /**
     */
    private $id;

    /**
     * @var string
     *
     */
    private $code;

    /**
     * @var string
     *
     */
    private $label;

    /**
     * @var string
     *
     */
    private $type;

    /**
     * @var string
     *
     */
    private $value;

    /**
     * @var null|CmsPage
     *
     */
    private $page;

    /**
     * @var mixed
     *
     */
    private $media;

    private $slider;

    public function __construct()
    {
        $this->slider = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

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
     * @return mixed
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param mixed $media
     */
    public function setMedia($media): void
    {
        $this->media = $media;
    }

    /**
     * @return Collection|CmsContentSlider[]
     */
    public function getSlider(): Collection
    {
        return $this->slider;
    }

    public function addSlider($slider): self
    {
        if (!$this->slider->contains($slider)) {
            $this->slider[] = $slider;
            $slider->setContent($this);
        }

        return $this;
    }

    public function removeSlider($slider): self
    {
        if ($this->slider->contains($slider)) {
            $this->slider->removeElement($slider);
            // set the owning side to null (unless already changed)
            if ($slider->getContent() === $this) {
                $slider->setContent(null);
            }
        }

        return $this;
    }
}
