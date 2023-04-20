<?php

namespace WebEtDesign\CmsBundle\Registry;

use WebEtDesign\CmsBundle\CMS\ConfigurationInterface;
use WebEtDesign\CmsBundle\CMS\Template\ComponentInterface;

interface TemplateRegistryInterface
{

    public function get(string $code): ComponentInterface;

    public function getList(string $type, string $collection = null): array;

    public function getChoiceList(string $type, string $collection = null): array;

    public function getConfigs(): array;

    public function setConfiguration(ConfigurationInterface $configuration): TemplateRegistryInterface;

    public function getConfiguration(): ?ConfigurationInterface;

}
