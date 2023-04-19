<?php

namespace WebEtDesign\CmsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WebEtDesign\CmsBundle\CMS\ConfigurationInterface;
use WebEtDesign\CmsBundle\Registry\BlockRegistry;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

class ConfigurationPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        $blockRegistry    = $container->getDefinition(BlockRegistry::class);
        $templateRegistry = $container->getDefinition(TemplateRegistry::class);

        foreach ($container->findTaggedServiceIds('wd_cms.configuration') as $id => $tags) {
            $container->addAliases([ConfigurationInterface::class => $id]);
            $definition = $container->findDefinition($id);
            $definition->setPublic(true);

            $definition->addMethodCall('setBlockRegistry', [$blockRegistry]);
            $definition->addMethodCall('setTemplateRegistry', [$templateRegistry]);

            $definition->addMethodCall('init');
        }
    }
}
