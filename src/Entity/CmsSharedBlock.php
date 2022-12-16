<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Type;
use WebEtDesign\CmsBundle\Repository\CmsSharedBlockRepository;

/**
 * @ORM\Entity(repositoryClass="WebEtDesign\CmsBundle\Repository\CmsSharedBlockRepository")
 * @ORM\Table(name="cms__shared_block")
 */
#[ORM\Entity(repositoryClass: CmsSharedBlockRepository::class)]
#[ORM\Table(name: "cms__shared_block")]
class CmsSharedBlock
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]

    private ?string $code = null;

    /**
     * @var string | null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]

    private ?string $label = null;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active;

    /**
     * @var ArrayCollection|PersistentCollection
     * @ORM\OneToMany(targetEntity="WebEtDesign\CmsBundle\Entity\CmsContent", mappedBy="sharedBlockParent", cascade={"persist", "remove"})
     */
    #[ORM\OneToMany(targetEntity: CmsContent::class, mappedBy: "sharedBlockParent", cascade: ["persist", "remove"])]
    private Collection $contents;

    /**
     * @var string | null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]

    private ?string $template = null;

    /**
     * @var CmsSite|null
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsSite", inversedBy="sharedBlocks")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="id", onDelete="SET NULL")
     */
    #[ORM\ManyToOne(targetEntity: CmsSite::class, inversedBy: "sharedBlocks")]
    #[ORM\JoinColumn(name: "site_id", referencedColumnName: "id", onDelete: "SET NULL")]
    private ?CmsSite $site = null;

    public $indexedContent = null;

    public function __construct()
    {
        $this->contents    = new ArrayCollection();
        $this->setActive(false);
    }

    public function __toString()
    {
        return (string)$this->getLabel();
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
     * @deprecated
     * @return bool
     */
    public function isPublic(): ?bool
    {
        return null;
    }

    /**
     * @param bool $public
     * @deprecated
     * @return CmsSharedBlock
     */
    public function setPublic(bool $public): CmsSharedBlock
    {
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
     * @param string|null $code
     * @return CmsSharedBlock
     */
    public function setCode(?string $code): CmsSharedBlock
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @param CmsSite|null $site
     * @return CmsSharedBlock
     */
    public function setSite(?CmsSite $site): CmsSharedBlock
    {
        $this->site = $site;

        return $this;
    }

    /**
     * @return CmsSite|null
     */
    public function getSite(): ?CmsSite
    {
        return $this->site;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }
}
