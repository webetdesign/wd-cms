<?php

namespace WebEtDesign\CmsBundle\Factory;

use Symfony\Component\DependencyInjection\ServiceLocator;
use WebEtDesign\CmsBundle\CmsBlock\AbstractBlock;
use WebEtDesign\CmsBundle\DependencyInjection\Models\BlockDefinition;

class BlockFactory
{
    private ServiceLocator $blocks;
    private array          $configs;

    public function __construct(ServiceLocator $blocks, array $configs)
    {
        $this->blocks  = $blocks;
        $this->configs = $configs;
    }

    public function get(BlockDefinition $config): AbstractBlock
    {
        return $this->mount($config);
    }

    protected function mount(BlockDefinition $config): AbstractBlock
    {
        $block = $this->getServices($config->getType());

        if (!empty($config->getHelp())) {
            $block->setHelp($config->getHelp());
        }

        $block->setOpen($config->isOpen());

        $block->setCode($config->getCode());

        $block->setLabel(!empty($config->getLabel()) ? $config->getLabel() : $config->getCode());

        $block->setSettings(array_merge($block->getSettings(), $config->getSettings()));

        $block->setFormOptions(array_merge($block->getFormOptions(), $config->getFormOptions()));

        $block->setBlocks($config->getBlocks());

        $block->setTemplate($config->getTemplate());

        $block->setAvailableBlocks($config->getAvailableBlocks());

        return $block;
    }

    protected function getConfig($name): array
    {
        if (!isset($this->configs[$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown block config "%s". The registered block configs are: %s',
                $name, implode(', ', array_keys($this->configs))));
        };

        return $this->configs[$name];
    }

    protected function getServices(string $name): AbstractBlock
    {
        if (!$this->blocks->has($name)) {
            throw new \InvalidArgumentException(sprintf('Unknown block "%s". The registered block are: %s',
                $name, implode(', ', array_keys($this->blocks->getProvidedServices()))));
        };

        return $this->blocks->get($name);
    }

    /**
     * @return array
     */
    public function getFormThemes(): array
    {
        return $this->formThemes;
    }
}
