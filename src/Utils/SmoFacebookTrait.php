<?php

namespace WebEtDesign\CmsBundle\Utils;

use Doctrine\ORM\Mapping as ORM;

trait SmoFacebookTrait
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="fb_title", type="string", nullable=true)
     */
    private $fb_title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fb_type", type="string", nullable=true)
     */
    private $fb_type;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fb_url", type="string", nullable=true)
     */
    private $fb_url;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fb_image", type="string", nullable=true)
     */
    private $fb_image;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fb_description", type="text", nullable=true)
     */
    private $fb_description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fb_site_name", type="string", nullable=true)
     */
    private $fb_site_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fb_admins", type="string", nullable=true)
     */
    private $fb_admins;

    /**
     * @return string|null
     */
    public function getFbTitle(): ?string
    {
        return $this->fb_title;
    }

    /**
     * @param string|null $fb_title
     * @return SmoFacebookTrait
     */
    public function setFbTitle(?string $fb_title): SmoFacebookTrait
    {
        $this->fb_title = $fb_title;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFbType(): ?string
    {
        return $this->fb_type;
    }

    /**
     * @param string|null $fb_type
     * @return SmoFacebookTrait
     */
    public function setFbType(?string $fb_type): SmoFacebookTrait
    {
        $this->fb_type = $fb_type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFbUrl(): ?string
    {
        return $this->fb_url;
    }

    /**
     * @param string|null $fb_url
     * @return SmoFacebookTrait
     */
    public function setFbUrl(?string $fb_url): SmoFacebookTrait
    {
        $this->fb_url = $fb_url;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFbImage(): ?string
    {
        return $this->fb_image;
    }

    /**
     * @param string|null $fb_image
     * @return SmoFacebookTrait
     */
    public function setFbImage(?string $fb_image): SmoFacebookTrait
    {
        $this->fb_image = $fb_image;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFbDescription(): ?string
    {
        return $this->fb_description;
    }

    /**
     * @param string|null $fb_description
     * @return SmoFacebookTrait
     */
    public function setFbDescription(?string $fb_description): SmoFacebookTrait
    {
        $this->fb_description = $fb_description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFbSiteName(): ?string
    {
        return $this->fb_site_name;
    }

    /**
     * @param string|null $fb_site_name
     * @return SmoFacebookTrait
     */
    public function setFbSiteName(?string $fb_site_name): SmoFacebookTrait
    {
        $this->fb_site_name = $fb_site_name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFbAdmins(): ?string
    {
        return $this->fb_admins;
    }

    /**
     * @param string|null $fb_admins
     * @return SmoFacebookTrait
     */
    public function setFbAdmins(?string $fb_admins): SmoFacebookTrait
    {
        $this->fb_admins = $fb_admins;
        return $this;
    }

}
