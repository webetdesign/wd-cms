<?php

namespace WebEtDesign\CmsBundle\Registry;

class BlockFormThemeRegistry
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
     * @return BlockFormThemeRegistry
     */
    public function setThemes(array $themes): BlockFormThemeRegistry
    {
        $this->themes = $themes;
        return $this;
    }

    /**
     * @param array $themes
     * @return BlockFormThemeRegistry
     */
    public function addThemes(array $themes): BlockFormThemeRegistry
    {
        foreach ($themes as $theme) {
            $this->addTheme($theme);
        }

        return $this;
    }

    /**
     * @param string $theme
     * @return BlockFormThemeRegistry
     */
    public function addTheme(string $theme): BlockFormThemeRegistry
    {
        if (!in_array($theme, $this->themes)) {
            $this->themes[] = $theme;
        }
        return $this;
    }
}
