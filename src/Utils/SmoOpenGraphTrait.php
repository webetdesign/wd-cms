<?php

namespace WebEtDesign\CmsBundle\Utils;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait SmoOpenGraphTrait
 * @package WebEtDesign\CmsBundle\Utils
 */
trait SmoOpenGraphTrait
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="og_title", type="string", nullable=true)
     */
    private $og_title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="og_type", type="string", nullable=true)
     */
    private $og_type;

//    /**
//     * @var Media|null
//     *
//     * @ORM\ManyToOne(targetEntity="App\Entity\Media", cascade={"persist"})
//     */
//    private $og_image;
//    TODO Convert WDMedia

    /**
     * @var string|null
     *
     * @ORM\Column(name="og_description", type="text", nullable=true)
     */
    private $og_description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="og_site_name", type="string", nullable=true)
     */
    private $og_site_name;

    /**
     * @return string|null
     */
    public function getOgTitle(): ?string
    {
        return $this->og_title;
    }

    /**
     * @param string|null $og_title
     * @return SmoOpenGraphTrait
     */
    public function setOgTitle(?string $og_title)
    {
        $this->og_title = $og_title;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOgType(): ?string
    {
        return $this->og_type;
    }

    /**
     * @param string|null $og_type
     * @return SmoOpenGraphTrait
     */
    public function setOgType(?string $og_type)
    {
        $this->og_type = $og_type;
        return $this;
    }

//    /**
//     * @return Media|null
//     */
//    public function getOgImage(): ?Media
//    {
//        return $this->og_image;
//    }
//
//    /**
//     * @param Media|null $og_image
//     * @return SmoOpenGraphTrait
//     */
//    public function setOgImage(?Media $og_image)
//    {
//        $this->og_image = $og_image;
//        return $this;
//    }

    /**
     * @return string|null
     */
    public function getOgDescription(): ?string
    {
        return $this->og_description;
    }

    /**
     * @param string|null $og_description
     * @return SmoOpenGraphTrait
     */
    public function setOgDescription(?string $og_description)
    {
        $this->og_description = $og_description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOgSiteName(): ?string
    {
        return $this->og_site_name;
    }

    /**
     * @param string|null $og_site_name
     * @return SmoOpenGraphTrait
     */
    public function setOgSiteName(?string $og_site_name)
    {
        $this->og_site_name = $og_site_name;
        return $this;
    }

}
