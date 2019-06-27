<?php

namespace WebEtDesign\CmsBundle\Utils;

use Doctrine\ORM\Mapping as ORM;

trait SmoTwitterTrait
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_card", type="string", nullable=true)
     */
    private $twitter_card;

    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_site", type="string", nullable=true)
     */
    private $twitter_site;

    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_title", type="string", nullable=true)
     */
    private $twitter_title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_description", type="text", length=200, nullable=true)
     */
    private $twitter_description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_creator", type="string", nullable=true)
     */
    private $twitter_creator;

    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_image", type="string", nullable=true)
     */
    private $twitter_image;

    /**
     * @return string|null
     */
    public function getTwitterCard(): ?string
    {
        return $this->twitter_card;
    }

    /**
     * @param string|null $twitter_card
     * @return SmoTwitterTrait
     */
    public function setTwitterCard(?string $twitter_card): SmoTwitterTrait
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
     * @return SmoTwitterTrait
     */
    public function setTwitterSite(?string $twitter_site): SmoTwitterTrait
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
     * @return SmoTwitterTrait
     */
    public function setTwitterTitle(?string $twitter_title): SmoTwitterTrait
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
     * @return SmoTwitterTrait
     */
    public function setTwitterDescription(?string $twitter_description): SmoTwitterTrait
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
     * @return SmoTwitterTrait
     */
    public function setTwitterCreator(?string $twitter_creator): SmoTwitterTrait
    {
        $this->twitter_creator = $twitter_creator;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTwitterImage(): ?string
    {
        return $this->twitter_image;
    }

    /**
     * @param string|null $twitter_image
     * @return SmoTwitterTrait
     */
    public function setTwitterImage(?string $twitter_image): SmoTwitterTrait
    {
        $this->twitter_image = $twitter_image;
        return $this;
    }
}
