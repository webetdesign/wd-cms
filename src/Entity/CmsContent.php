<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;
use WebEtDesign\CmsBundle\Repository\CmsContentRepository;

#[ORM\Entity(repositoryClass: CmsContentRepository::class)]
#[ORM\Table(name: 'cms__content')]
#[Gedmo\Loggable(logEntryClass: CmsLogEntry::class)]
class CmsContent implements Loggable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $code = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $label = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Gedmo\Versioned]
    private string $type = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Versioned]
    private ?string $value = null;

    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: CmsPage::class, inversedBy: 'contents')]
    #[ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\Versioned]
    private ?CmsPage $page = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Gedmo\SortablePosition]
    #[Gedmo\Versioned]
    private ?int $position = null;

    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: CmsSharedBlock::class, inversedBy: 'contents')]
    #[ORM\JoinColumn(name: "shared_block_parent_id", referencedColumnName: 'id')]
    #[Gedmo\Versioned]
    private ?CmsSharedBlock $sharedBlockParent = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    #[Gedmo\Versioned]
    private ?bool $parent_heritance = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Gedmo\Versioned]
    private bool $active;

    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: CmsPageDeclination::class, inversedBy: 'contents')]
    #[ORM\JoinColumn(name: "declination_id", referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\Versioned]
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

    public function clone(): CmsContent
    {
        return (new CmsContent())
            ->setType($this->getType())
            ->setActive($this->getActive())
            ->setPosition($this->getPosition())
            ->setLabel($this->getLabel())
            ->setCode($this->getCode())
            ->setValue($this->getValue())
            ->setParentHeritance($this->getParentHeritance())
            ->setPage(null) // $this->getPage()
            ->setSharedBlockParent(null) // $this->getSharedBlockParent()
            ->setDeclination(null); // $this->getDeclination()
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
