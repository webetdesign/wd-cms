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

class CmsSite
{
    /**
     * @var int
     *
     */
    private $id;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string
     *
     */
    private $locale;

    /**
     * @var string
     *
     */
    private $host;

    /** @var boolean */
    private $hostMultilingual = 0;

    /** @var boolean */
    private $default = 0;

    /** @var ArrayCollection|PersistentCollection */
    private $pages;

    /** @var string */
    private $flagIcon;

    private $menu;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
    }

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
     * @return Collection|CmsPage[]
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(CmsPage $page): self
    {
        if (!$this->pages->contains($page)) {
            $this->pages[] = $page;
            $page->setSite($this);
        }

        return $this;
    }

    public function removePage(CmsPage $page): self
    {
        if ($this->pages->contains($page)) {
            $this->pages->removeElement($page);
            // set the owning side to null (unless already changed)
            if ($page->getSite() === $this) {
                $page->setSite(null);
            }
        }

        return $this;
    }

    /**
     * @param bool $hostMultilingual
     * @return CmsSite
     */
    public function setHostMultilingual(bool $hostMultilingual): CmsSite
    {
        $this->hostMultilingual = $hostMultilingual;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHostMultilingual(): bool
    {
        return $this->hostMultilingual;
    }

    /**
     * @param bool $default
     * @return CmsSite
     */
    public function setDefault(bool $default): CmsSite
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
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



}
