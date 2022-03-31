<?php

namespace WebEtDesign\CmsBundle\Manager;

class BlockFormThemesManager
{
    private array $themes = [];

    /**
     * @return array
     */
    public function getThemes(): array
    {
        return $this->themes;
    }

    /**
     * @param array $themes
     * @return BlockFormThemesManager
     */
    public function setThemes(array $themes): BlockFormThemesManager
    {
        $this->themes = $themes;
        return $this;
    }

    /**
     * @param array $themes
     * @return BlockFormThemesManager
     */
    public function addThemes(array $themes): BlockFormThemesManager
    {
        foreach ($themes as $theme) {
            $this->addTheme($theme);
        }

        return $this;
    }

    /**
     * @param string $theme
     * @return BlockFormThemesManager
     */
    public function addTheme(string $theme): BlockFormThemesManager
    {
        if (!in_array($theme, $this->themes)) {
            $this->themes[] = $theme;
        }
        return $this;
    }
}
