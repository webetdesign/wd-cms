<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;

class CmsSharedBlock
{
    private $id;

    /** @var string */
    private $code;

    /** @var string */
    private $label;

    /** @var boolean */
    private $public;

    /** @var boolean */
    private $active;

    /**
     * @var ArrayCollection|PersistentCollection
     */
    private $contents;

    /**
     * @var ArrayCollection
     */
    private $contentList;

    /**
     * @var null|string
     */
    private $template;

    /**
     * @inheritDoc
     */
    public function __construct() {
        $this->contentList = new ArrayCollection();
        $this->contents = new ArrayCollection();
        $this->setActive(false);
    }

    public function __toString()
    {
        return (string) $this->getLabel();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    /**
     * @param string $label
     * @return CmsSharedBlock
     */
    public function setLabel(string $label): CmsSharedBlock
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
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
            $content->setSharedBlockParent($this);
        }

        return $this;
    }

    public function removeContent(CmsContent $content): self
    {
        if ($this->contents->contains($content)) {
            $this->contents->removeElement($content);
            // set the owning side to null (unless already changed)
            if ($content->getSharedBlockParent() === $this) {
                $content->setSharedBlockParent(null);
            }
        }

        return $this;
    }

    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param string|null $template
     * @return CmsSharedBlock
     */
    public function setTemplate(?string $template): CmsSharedBlock
    {
        $this->template = $template;
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
     * @return CmsSharedBlock
     */
    public function setActive(bool $active): CmsSharedBlock
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return (bool)$this->public;
    }

    /**
     * @param bool $public
     * @return CmsSharedBlock
     */
    public function setPublic(bool $public): CmsSharedBlock
    {
        $this->public = $public;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return CmsSharedBlock
     */
    public function setCode(?string $code): CmsSharedBlock
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getContentList()
    {
        return $this->contentList;
    }

}
