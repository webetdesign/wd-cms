<?php

namespace WebEtDesign\CmsBundle\Entity;

use App\Entity\Media;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Sonata\MediaBundle\Model\MediaInterface;
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
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private $code;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private $label;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     *
     */
    private $value;

    /**
     * @var null|CmsPage
     * @Gedmo\SortableGroup()
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsPage", inversedBy="contents")
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $page;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @Gedmo\SortablePosition()
     */
    private $position;

    /**
     * @var null|CmsSharedBlock
     * @Gedmo\SortableGroup()
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsSharedBlock", inversedBy="contents")
     * @ORM\JoinColumn(name="shared_block_parent_id", referencedColumnName="id")
     */
    private $sharedBlockParent;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsContentHasSharedBlock", mappedBy="content", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $sharedBlockList;

    /**
     * @var MediaInterface
     */
    private $media;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $parent_heritance;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * Mapping Relation in WebEtDesignCmsExtension
     * @Gedmo\SortableGroup()
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsPageDeclination", inversedBy="contents")
     * @ORM\JoinColumn(name="declination_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $declination;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $help;

    public bool $collapseOpen = false;

    public function __toString()
    {
        return $this->label;
    }

    public function __construct()
    {
        $this->sharedBlockList  = new ArrayCollection();
        $this->active           = true;
        $this->parent_heritance = false;
    }

    public function isSet()
    {
        switch (true) {
            case $this->getValue() !== null:
                return true;
            case $this->getMedia() !== null:
                return true;
            case $this->getSharedBlockList() !== null && $this->getSharedBlockList()->count() > 0:
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

    public function getMedia(): ?MediaInterface
    {
        return $this->media;
    }

    public function setMedia($media): self
    {
        $this->media = $media;

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
     * @return Collection|CmsContentHasSharedBlock[]
     */
    public function getSharedBlockList(): Collection
    {
        return $this->sharedBlockList;
    }

    /**
     * @param mixed $sharedBlockList
     * @return CmsContent
     */
    public function setSharedBlockList($sharedBlockList)
    {
        $this->sharedBlockList = $sharedBlockList;

        return $this;
    }

    public function addSharedBlockList(CmsContentHasSharedBlock $sharedBlockList): self
    {
        if (!$this->sharedBlockList->contains($sharedBlockList)) {
            $this->sharedBlockList[] = $sharedBlockList;
            $sharedBlockList->setContent($this);
        }

        return $this;
    }

    public function removeSharedBlockList(CmsContentHasSharedBlock $sharedBlockList): self
    {
        if ($this->sharedBlockList->contains($sharedBlockList)) {
            $this->sharedBlockList->removeElement($sharedBlockList);
            // set the owning side to null (unless already changed)
            if ($sharedBlockList->getContent() === $this) {
                $sharedBlockList->setContent(null);
            }
        }

        return $this;
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
     * @param string $help
     * @return CmsContent
     */
    public function setHelp(?string $help): CmsContent
    {
        $this->help = $help;

        return $this;
    }

    /**
     * @return string
     */
    public function getHelp(): ?string
    {
        return $this->help;
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
