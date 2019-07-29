<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;

class CmsSharedBlock
{
    private $id;

    /** @var string */
    private $title;

    /** @var boolean */
    private $active;

    /**
     *
     * @var ArrayCollection|PersistentCollection
     *
     */
    private $contents;

    /**
     * @var null|string
     *
     */
    private $template;

    /**
     * @inheritDoc
     */
    public function __construct() {
        $this->contents = new ArrayCollection();
        $this->setActive(false);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $title
     * @return CmsSharedBlock
     */
    public function setTitle(string $title): CmsSharedBlock
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
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

}
