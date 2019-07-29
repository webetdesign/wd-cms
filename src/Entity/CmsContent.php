<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;


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
     * @var null|CmsSharedBlock
     *
     */
    private $sharedBlock;

    /**
     * @var mixed
     *
     */
    private $media;
    
    /** @var boolean */
    private $active;

    /**
     *
     * @var ArrayCollection|PersistentCollection
     *
     */
    private $sliders;

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return $this->label;
    }

    public function __construct()
    {
        $this->sliders = new ArrayCollection();
        $this->active = true;
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
     * @return CmsSharedBlock|null
     */
    public function getSharedBlock(): ?CmsSharedBlock
    {
        return $this->sharedBlock;
    }

    /**
     * @param CmsSharedBlock|null $sharedBlock
     */
    public function setSharedBlock(?CmsSharedBlock $sharedBlock): void
    {
        $this->sharedBlock = $sharedBlock;
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

    public function setSliders($sliders)
    {
        if (count($sliders) > 0) {
            foreach ($sliders as $i) {
                $this->addSlider($i);
            }
        }

        return $this;
    }

    /**
     * @return Collection|CmsContentSlider[]
     */
    public function getSliders(): ?Collection
    {
        return $this->sliders;
    }

    public function addSlider(CmsContentSlider $slider): self
    {
        if (!$this->sliders->contains($slider)) {
            $slider->setContent($this);
            $this->sliders[] = $slider;
        }

        return $this;
    }

    public function removeSlider(CmsContentSlider $slider): self
    {
        if ($this->sliders->contains($slider)) {
            $this->sliders->removeElement($slider);
            // set the owning side to null (unless already changed)
            if ($slider->getContent() === $this) {
                $slider->setContent(null);
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
     * @return CmsContent
     */
    public function setActive(bool $active): CmsContent
    {
        $this->active = $active;
        return $this;
    }

}
