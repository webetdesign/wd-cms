<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\Debug\Exception\UndefinedMethodException;
use WebEtDesign\CmsBundle\Utils\SmoFacebookTrait;
use WebEtDesign\CmsBundle\Utils\SmoTwitterTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;


/**
 * @ORM\Entity(repositoryClass="WebEtDesign\CmsBundle\Repository\CmsPageDeclinationRepository")
 * @ORM\Table(name="cms__page_declination")
 */
class CmsPageDeclination
{
    use SeoAwareTrait;
    use SmoFacebookTrait;
    use SmoTwitterTrait;


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    private $page;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private $technic_name;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private $locale;

    /**
     *
     * @var ArrayCollection|PersistentCollection
     *
     */
    private $contents;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", length=255, nullable=false, options={"default": false})
     */
    private $active = false;

    /**
     * @var string
     * @ORM\Column(type="text", length=255, nullable=false)
     */
    private $params = '[]';

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->contents = new ArrayCollection();
        $this->setActive(false);
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string)$this->getTitle();
    }

    public function getPath()
    {
        $params = json_decode($this->getParams(), true);
        $pagePath = $this->getPage()->getRoute()->getPath();
        $path     = preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($params) {
            return $params[$matches[1]] ?? '';
        }, $pagePath);
        return preg_replace('/\/*$/', '', $path);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getContents()
    {
        return $this->contents;
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
            $content->setDeclination($this);
        }

        return $this;
    }

    public function removeContent(CmsContent $content): self
    {
        if ($this->contents->contains($content)) {
            $this->contents->removeElement($content);
            // set the owning side to null (unless already changed)
            if ($content->getDeclination() === $this) {
                $content->setDeclination(null);
            }
        }

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
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @param mixed $page
     * @return CmsPageDeclination
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return CmsPage
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param string $params
     * @return CmsPageDeclination
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @return string
     */
    public function getParams(): string
    {
        return $this->params;
    }

    public function getSeoTitle(): ?string
    {
        if ($this->seo_title === null) {
            return '';
        }

        return $this->seo_title;
    }

    public function getTechnicName(): ?string
    {
        return $this->technic_name;
    }

    public function setTechnicName(?string $technic_name): self
    {
        $this->technic_name = $technic_name;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

}
