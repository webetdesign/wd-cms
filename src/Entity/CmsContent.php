<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="WebEtDesign\CmsBundle\Repository\CmsContentRepository")
 * @ORM\Table(name="cms__content")
 */
class CmsContent
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private ?string $code = null;

    /**
     * @var null|string
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private ?string $label = null;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private string $type = '';

    /**
     * @var null|string
     * @ORM\Column(type="text", nullable=true)
     *
     */
    private ?string $value = null;

    /**
     * @var null|CmsPage
     * @Gedmo\SortableGroup()
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsPage", inversedBy="contents")
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?CmsPage $page = null;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     * @Gedmo\SortablePosition()
     */
    private ?int $position = null;

    /**
     * @var null|CmsSharedBlock
     * @Gedmo\SortableGroup()
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsSharedBlock", inversedBy="contents")
     * @ORM\JoinColumn(name="shared_block_parent_id", referencedColumnName="id")
     */
    private ?CmsSharedBlock $sharedBlockParent = null;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $parent_heritance = null;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private bool $active;

    /**
     * Mapping Relation in WebEtDesignCmsExtension
     * @Gedmo\SortableGroup()
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsPageDeclination", inversedBy="contents")
     * @ORM\JoinColumn(name="declination_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?CmsPageDeclination $declination = null;

    public bool $collapseOpen = false;

    public function __toString()
    {
        return $this->label;
    }

    public function __construct()
    {
        $this->active           = true;
        $this->parent_heritance = false;
    }

    public function isSet(): bool
    {
        switch (true) {
            case $this->getValue() !== null:
                return true;
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
        $value = $this->value;
        if ($this->getParentHeritance()) {
            $content = $this->getParentContent();
            $value   = $content ? $content->getValue() : null;
        }

        return $value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getPage(): ?CmsPage
    {
        return $this->page;
    }

    public function setPage(?CmsPage $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getParentContent(): ?CmsContent
    {
        if (!$this->getPage()) {
            return null;
        }

        return $this->getPage()->getParent() ? $this->getPage()->getParent()->getContent($this->code) : null;
    }

    public function getSharedBlockParent(): ?CmsSharedBlock
    {
        return $this->sharedBlockParent;
    }

    public function setSharedBlockParent(?CmsSharedBlock $sharedBlockParent): self
    {
        $this->sharedBlockParent = $sharedBlockParent;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @return bool
     */
    public function isParentHeritance(): bool
    {
        return $this->parent_heritance;
    }

    public function setParentHeritance(bool $heritance): self
    {
        $this->parent_heritance = $heritance;

        return $this;
    }

    public function getParentHeritance(): ?bool
    {
        return $this->parent_heritance;
    }

    /**
     * @param mixed $declination
     * @return CmsContent
     */
    public function setDeclination($declination)
    {
        $this->declination = $declination;

        return $this;
    }

    /**
     * @return CmsPageDeclination
     */
    public function getDeclination()
    {
        return $this->declination;
    }

    /**
     * @param int $position
     * @return CmsContent
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }
}
