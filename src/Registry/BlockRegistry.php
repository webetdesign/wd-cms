<?php

namespace WebEtDesign\CmsBundle\Registry;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Twig\Environment;
use WebEtDesign\CmsBundle\CMS\Block\AbstractBlock;
use WebEtDesign\CmsBundle\CMS\Configuration\BlockDefinition;
use WebEtDesign\CmsBundle\CMS\ConfigurationInterface;
use WebEtDesign\CmsBundle\Form\Transformer\CmsBlockTransformer;

class BlockRegistry implements BlockRegistryInterface
{
    private ServiceLocator         $blocks;
    private Environment            $twig;
    private EntityManagerInterface $em;
    private ConfigurationInterface $configuration;


    public function __construct(
        ServiceLocator $blocks,
        Environment $twig,
        EntityManagerInterface $em,
        ConfigurationInterface $configuration,
    ) {
        $this->blocks        = $blocks;
        $this->twig          = $twig;
        $this->em            = $em;
        $this->configuration = $configuration;
    }

    public function get(BlockDefinition $config): AbstractBlock
    {
        return $this->mount($config);
    }

    protected function mount(BlockDefinition $config): AbstractBlock
    {
        $block = clone $this->getServices($config->getType());

        if (!empty($config->getHelp())) {
            $block->setHelp($config->getHelp());
        }

        $block->setOpen($config->isOpen());

        $block->setCode($config->getCode());

        $block->setLabel(!empty($config->getLabel()) ? $config->getLabel() : $config->getCode());

        $block->setSettings(array_merge($block->getSettings(), $config->getSettings()));

        $block->setFormOptions(array_merge($block->getFormOptions(), $config->getFormOptions()));

        $block->setOptions($config->getOptions());

        $block->setBlocks($config->getBlocks());

        $block->setModelTransformer(new CmsBlockTransformer($this->em));

        if (!empty($config->getTemplate())) {
            $block->setTemplate($config->getTemplate());
        }
        if ($config->getTemplate() === false) {
            $block->setTemplate(null);
        }

        $block->setTwig($this->twig);

        $block->setAvailableBlocks($config->getAvailableBlocks());

        $block->setConfiguration($this->configuration);

        return $block;
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
     * @param Environment $twig
     * @return BlockRegistry
     */
    public function setTwig(Environment $twig): BlockRegistry
    {
        $this->twig = $twig;
        return $this;
    }

    /**
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }
}
