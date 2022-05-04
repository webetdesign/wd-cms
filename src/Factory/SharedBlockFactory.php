<?php

namespace WebEtDesign\CmsBundle\Factory;

use WebEtDesign\CmsBundle\CmsTemplate\TemplateInterface;

class SharedBlockFactory extends AbstractTemplateFactory
{
    protected function mount($code): TemplateInterface
    {
        $config = $this->getConfig($code);

        return $this->getServices($code);
    }

    protected function getConfig($code): array
    {
        if (!isset($this->configs[$code])) {
            throw new \InvalidArgumentException(sprintf('Unknown sharedBlock config "%s". The registered sharedBlock configs are: %s',
                $code, implode(', ', array_keys($this->configs))));
        };

        return $this->configs[$code];
    }

    protected function getServices(string $code): TemplateInterface
    {
        if (!$this->serviceLocator->has($code)) {
            throw new \InvalidArgumentException(sprintf('Unknown sharedBlock "%s". The registered sharedBlock are: %s',
                $code, implode(', ', array_keys($this->serviceLocator->getProvidedServices()))));
        };

        $service = $this->serviceLocator->get($code);

        if (empty($service->getCode())) {
            $service->setCode($code);
        }

        return $service;
    }


}