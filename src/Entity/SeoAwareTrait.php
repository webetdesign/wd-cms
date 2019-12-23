<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait SeoAwareTrait
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $seo_title;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $seo_description;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $seo_keywords;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $seo_breadcrumb;

    public function getSeoTitle(): ?string
    {
        if ($this->seo_title === null) {
            return $this->__toString();
        }

        return $this->seo_title;
    }

    public function setSeoTitle($seo_title): self
    {
        $this->seo_title = $seo_title;

        return $this;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seo_description;
    }

    public function setSeoDescription($seo_description): self
    {
        $this->seo_description = $seo_description;

        return $this;
    }

    public function getSeoKeywords(): ?string
    {
        return $this->seo_keywords;
    }

    public function setSeoKeywords($seo_keywords): self
    {
        $this->seo_keywords = $seo_keywords;

        return $this;
    }

    /**
     * @return string
     */
    public function getSeoBreadcrumb(): ?string
    {
        return $this->seo_breadcrumb;
    }

    /**
     * @param string $seo_breadcrumb
     * @return self
     */
    public function setSeoBreadcrumb($seo_breadcrumb): self
    {
        $this->seo_breadcrumb = $seo_breadcrumb;

        return $this;
    }
}
