<?php

namespace WebEtDesign\CmsBundle\Entity;

trait SeoAwareTrait
{
    /**
     * @var string
     *
     */
    private $seo_title;

    /**
     * @var string
     *
     */
    private $seo_description;

    /**
     * @var string
     *
     */
    private $seo_keywords;

    public function getSeoTitle(): ?string
    {
        if($this->seo_title === null){
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
}
