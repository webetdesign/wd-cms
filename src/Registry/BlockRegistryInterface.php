<?php

namespace WebEtDesign\CmsBundle\Registry;



use WebEtDesign\CmsBundle\CMS\Block\AbstractBlock;
use WebEtDesign\CmsBundle\CMS\Configuration\BlockDefinition;

interface BlockRegistryInterface
{

    public function get(BlockDefinition $config): AbstractBlock;

}
