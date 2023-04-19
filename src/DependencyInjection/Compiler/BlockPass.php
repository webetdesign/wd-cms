<?php

namespace WebEtDesign\CmsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WebEtDesign\CmsBundle\Manager\BlockFormThemesManager;
use WebEtDesign\CmsBundle\Registry\BlockFormThemeRegistry;
use WebEtDesign\CmsBundle\Registry\BlockRegistry;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

class BlockPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $ManagerDefinition = $container->getDefinition(BlockFormThemeRegistry::class);

        foreach ($container->findTaggedServiceIds('wd_cms.block') as $id => $tags) {
            $definition = $container->findDefinition($id);

            $definition->setPublic(true);

            foreach ($tags as $tag) {
                if (isset($tag['formTheme']) && !empty($tag['formTheme'])) {
                    $ManagerDefinition->addMethodCall('addTheme', [$tag['formTheme']]);
                }
            }
        }
    }
}
