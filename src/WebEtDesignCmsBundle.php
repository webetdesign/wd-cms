<?php

namespace WebEtDesign\CmsBundle;

use App\Cms\Configuration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use WebEtDesign\CmsBundle\DependencyInjection\Compiler\BlockPass;
use WebEtDesign\CmsBundle\DependencyInjection\Compiler\ConfigurationPass;
use WebEtDesign\CmsBundle\DependencyInjection\Compiler\TemplatePass;

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
        $container->addCompilerPass(new TemplatePass());
        $container->addCompilerPass(new ConfigurationPass());
    }

}
