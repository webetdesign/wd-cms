<?php

namespace WebEtDesign\CmsBundle\Factory;

use Symfony\Component\DependencyInjection\ServiceLocator;
use WebEtDesign\CmsBundle\CmsTemplate\TemplateInterface;

abstract class AbstractTemplateFactory implements TemplateFactoryInterface
{
    protected ServiceLocator $serviceLocator;
    protected array          $configs;

    public function __construct(ServiceLocator $templates, array $configs)
    {
        $this->serviceLocator = $templates;
        $this->configs        = $configs;
    }

    public function get($code): TemplateInterface
    {
        return $this->mount($code);
    }

    public function getTemplateList($collection = null): array
    {
        $tpls = [];
        foreach ($this->serviceLocator->getProvidedServices() as $key => $id) {
            $tpl = $this->getServices($key);
            if ($tpl && ($tpl->getCollections() === null || in_array($collection, $tpl->getCollections()))) {
                $tpls[$key] = $tpl;
            }
        }

        return $tpls;
    }

    public function getTemplateChoices($collection = null): array
    {
        $tpls = [];
        foreach ($this->serviceLocator->getProvidedServices() as $key => $id) {
            $tpl = $this->getServices($key);
            if ($tpl && ($tpl->getCollections() === null || $collection === null || in_array($collection, $tpl->getCollections()))) {
                $tpls[$tpl->getLabel()] = $key;
            }
        }

        return $tpls;
    }
}
