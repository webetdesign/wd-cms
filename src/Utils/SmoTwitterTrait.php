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
     * @ORM\Column(name="twitter_description", type="text", nullable=true)
     */
    private $twitter_description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="twitter_creator", type="string", nullable=true)
     */
    private $twitter_creator;

//    /**
//     * @var Media|null
//     *
//     * @ORM\ManyToOne(targetEntity="App\Entity\Media", cascade={"persist"})
//     */
//    private $twitter_image;
//    TODO convert WDMedia

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
    public function setTwitterCard(?string $twitter_card)
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
    public function setTwitterSite(?string $twitter_site)
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
    public function setTwitterTitle(?string $twitter_title)
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
    public function setTwitterDescription(?string $twitter_description)
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
    public function setTwitterCreator(?string $twitter_creator)
    {
        $this->twitter_creator = $twitter_creator;
        return $this;
    }

//    /**
//     * @return Media|null
//     */
//    public function getTwitterImage(): ?Media
//    {
//        return $this->twitter_image;
//    }
//
//    /**
//     * @param Media|null $twitter_image
//     * @return SmoTwitterTrait
//     */
//    public function setTwitterImage(?Media $twitter_image)
//    {
//        $this->twitter_image = $twitter_image;
//        return $this;
//    }
}
