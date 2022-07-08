<?php

namespace WebEtDesign\CmsBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use WebEtDesign\CmsBundle\DependencyInjection\Compiler\BlockPass;
use WebEtDesign\CmsBundle\DependencyInjection\Compiler\ConfigurationPass;
use WebEtDesign\CmsBundle\DependencyInjection\Compiler\PageTemplatePass;
use WebEtDesign\CmsBundle\DependencyInjection\Compiler\SharedBlockPass;

/**
 * References:
 * @link http://symfony.com/doc/current/book/bundles.html
 */
class WebEtDesignCmsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new BlockPass());
        $container->addCompilerPass(new PageTemplatePass());
        $container->addCompilerPass(new SharedBlockPass());
        $container->addCompilerPass(new ConfigurationPass());
    }

}
