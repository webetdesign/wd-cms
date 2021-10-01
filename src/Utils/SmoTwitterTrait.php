<?php

namespace WebEtDesign\CmsBundle\Utils;

use Doctrine\ORM\Mapping as ORM;
use WebEtDesign\MediaBundle\Entity\Media;

/**
 * Trait SmoTwitterTrait
 * @package WebEtDesign\CmsBundle\Utils
 * @deprecated Use the trait in wd-seo-bundle
 */
trait SmoTwitterTrait
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_card", type="string", nullable=true)
     */
    private ?string $twitter_card = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_site", type="string", nullable=true)
     */
    private ?string $twitter_site = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_title", type="string", nullable=true)
     */
    private ?string $twitter_title = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_description", type="text", nullable=true)
     */
    private ?string $twitter_description = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_creator", type="string", nullable=true)
     */
    private ?string $twitter_creator = null;

    /**
     * @var Media|null
     *
     * @ORM\ManyToOne(targetEntity="WebEtDesign\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private ?Media $twitter_image = null;

    /**
     * @return string|null
     */
    public function getTwitterCard(): ?string
    {
        return $this->twitter_card;
    }

    /**
     * @param string|null $twitter_card
     */
    public function setTwitterCard(?string $twitter_card): self
    {
        $this->twitter_card = $twitter_card;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTwitterSite(): ?string
    {
        return $this->twitter_site;
    }

    /**
     * @param string|null $twitter_site
     */
    public function setTwitterSite(?string $twitter_site): self
    {
        $this->twitter_site = $twitter_site;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTwitterTitle(): ?string
    {
        return $this->twitter_title;
    }

    /**
     * @param string|null $twitter_title
     */
    public function setTwitterTitle(?string $twitter_title): self
    {
        $this->twitter_title = $twitter_title;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTwitterDescription(): ?string
    {
        return $this->twitter_description;
    }

    /**
     * @param string|null $twitter_description
     */
    public function setTwitterDescription(?string $twitter_description): self
    {
        $this->twitter_description = $twitter_description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTwitterCreator(): ?string
    {
        return $this->twitter_creator;
    }

    /**
     * @param string|null $twitter_creator
     */
    public function setTwitterCreator(?string $twitter_creator): self
    {
        $this->twitter_creator = $twitter_creator;
        return $this;
    }

    /**
     * @return Media|null
     */
    public function getTwitterImage(): ?Media
    {
        return $this->twitter_image;
    }

    /**
     * @param Media|null $twitter_image
     * @return SmoTwitterTrait
     */
    public function setTwitterImage(?Media $twitter_image): self
    {
        $this->twitter_image = $twitter_image;
        return $this;
    }
}
