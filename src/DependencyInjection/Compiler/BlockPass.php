<?php

namespace WebEtDesign\CmsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WebEtDesign\CmsBundle\Manager\BlockFormThemesManager;

class BlockPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        $ManagerDefinition = $container->getDefinition(BlockFormThemesManager::class);

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
