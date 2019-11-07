<?php
/**
 * Created by PhpStorm.
 * User: Leo MEYER
 * Date: 07/08/2019
 * Time: 15:24
 */

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="WebEtDesign\CmsBundle\Repository\CmsSiteRepository")
 * @ORM\Table(name="cms__site")
 */
class CmsSite
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private $label;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private $locale;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private $host;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default" : 0})
     *
     */
    private $hostMultilingual = false;

    /**
     * @var boolean
     * @ORM\Column(name="`default`", type="boolean", options={"default" : 0})
     *
     */
    private $default;

    /**
     * @var CmsPage
     * Mapping Relation in WebEtDesignCmsExtension
     */
    private $page;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private $flagIcon;

    /**
     * @var mixed $menu
     */
    private $menu;

    /**
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $templateFilter;

    public function __construct() {}

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string) $this->getLabel() . (!empty($this->getLocale()) ? ' - ' . $this->getLocale() : '');
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(?string $host): void
    {
        $this->host = $host;
    }

    /**
     * @param string|null $label
     * @return CmsSite
     */
    public function setLabel(?string $label): CmsSite
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param bool $hostMultilingual
     */
    public function setHostMultilingual(bool $hostMultilingual)
    {
        $this->hostMultilingual = $hostMultilingual;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHostMultilingual(): ?bool
    {
        return $this->hostMultilingual;
    }

    /**
     * @param bool $default
     */
    public function setDefault(bool $default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault(): ?bool
    {
        return $this->default;
    }

    /**
     * @return string
     */
    public function getFlagIcon(): ?string
    {
        return $this->flagIcon;
    }

    /**
     * @param string $flagIcon
     */
    public function setFlagIcon(?string $flagIcon): void
    {
        $this->flagIcon = $flagIcon;
    }

    /**
     * @return mixed
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * @param mixed $menu
     */
    public function setMenu($menu): void
    {
        $this->menu = $menu;
    }

    public function getSlug(){
        $slugify = new Slugify();
        return $slugify->slugify($this->getLabel(), "_");
    }

    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param $page
     */
    public function setPage($page): void
    {
        $this->page = $page;
    }

    /**
     * @param mixed $templateFilter
     * @return CmsSite
     */
    public function setTemplateFilter($templateFilter)
    {
        $this->templateFilter = $templateFilter;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTemplateFilter()
    {
        return $this->templateFilter;
    }


}
