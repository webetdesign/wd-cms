<?php

namespace WebEtDesign\CmsBundle\CMS;

use WebEtDesign\CmsBundle\Enum\CmsVarsDelimiterEnum;
use WebEtDesign\CmsBundle\Registry\BlockRegistryInterface;
use WebEtDesign\CmsBundle\Registry\TemplateRegistryInterface;
use WebEtDesign\CmsBundle\Vars\CmsVarsBag;

interface ConfigurationInterface
{
    public function getBlockRegistry(): BlockRegistryInterface;

    public function setBlockRegistry(BlockRegistryInterface $blockRegistry): AbstractConfiguration;

    public function getTemplateRegistry(): TemplateRegistryInterface;

    public function setTemplateRegistry(TemplateRegistryInterface $templateRegistry): AbstractConfiguration;

    public function getDisabledTemplate(): array;

    public function getCmsVarsDelimiter(): CmsVarsDelimiterEnum;

    public function setCmsVarsDelimiter(CmsVarsDelimiterEnum $cmsVarsDelimiter): AbstractConfiguration;

    public function getVarsBag(): ?CmsVarsBag;
}
