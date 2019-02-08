<?php

namespace WebEtDesign\CmsBundle\Model;

trait SeoAwareTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="seo_title", type="string", nullable=true)
     */
    private $seo_title;

    /**
     * @var string
     *
     * @ORM\Column(name="seo_description", type="text", nullable=true)
     */
    private $seo_description;

    /**
     * @var string
     *
     * @ORM\Column(name="seo_keywords", type="text", nullable=true)
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
