<?php

namespace WebEtDesign\CmsBundle\Registry;

use Symfony\Component\DependencyInjection\ServiceLocator;
use WebEtDesign\CmsBundle\CMS\ConfigurationInterface;
use WebEtDesign\CmsBundle\CMS\Template\ComponentInterface;
use WebEtDesign\CmsBundle\Vars\CmsVarsBag;
use WebEtDesign\CmsBundle\Vars\Compiler;

class TemplateRegistry implements TemplateRegistryInterface
{
    const TYPE_PAGE   = 'TYPE_PAGE';
    const TYPE_SHARED = 'TYPE_SHARED';

    private ServiceLocator          $serviceLocator;
    private array                   $configs;
    private ?ConfigurationInterface $configuration = null;

    public function __construct(ServiceLocator $templates, array $configs)
    {
        $this->serviceLocator = $templates;
        $this->configs        = $configs;
    }

    public function get(string $code): ComponentInterface
    {
        $config = $this->getConfig($code);

        $service = $this->serviceLocator->get($config['id']);

        $service->setVarsBag(new CmsVarsBag(new Compiler()));
        $service->configureVars($service->getVarsBag());

        $service->setCode($code);

        return $service;
    }

    private function getConfig(string $code)
    {
        return $this->configs[$code];
    }

    private function getConfigById(): array
    {
        $configs = [];
        foreach ($this->configs as $config) {
            $configs[$config['id']] = $config;
        }

        return $configs;
    }

    public function getList(string $type, string $collection = null): array
    {
        $configs = $this->getConfigById();

        $templates = [];
        foreach ($this->serviceLocator->getProvidedServices() as $id) {
            $config = $configs[$id];
            if (!$config) {
                continue;
            }

            $tpl = $this->get($config['code']);

            if (!$tpl) {
                continue;
            }

            $goodType       = $type === $config['type'];
            $goodCollection =
                $collection === null ||
                $tpl->getCollections() === null ||
                in_array($collection, $tpl->getCollections());

            if (!$goodType || !$goodCollection) {
                continue;
            }

            if (!empty($tpl->getCollections()) && $collection === null) {
                $colString = implode(', ', $tpl->getCollections());
            }


            $templates[$config['code']] = (!empty($colString) ? "[$colString] — " : '') . $tpl->getLabel();
        }

        return $templates;
    }

    public function getChoiceList(string $type, string $collection = null): array
    {
        return array_flip($this->getList($type, $collection));
    }

    /**
     * @return array
     */
    public function getConfigs(): array
    {
        return $this->configs;
    }

    /**
     * @param ConfigurationInterface|null $configuration
     * @return TemplateRegistry
     */
    public function setConfiguration(?ConfigurationInterface $configuration): TemplateRegistry
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return ConfigurationInterface|null
     */
    public function getConfiguration(): ?ConfigurationInterface
    {
        return $this->configuration;
    }
}
