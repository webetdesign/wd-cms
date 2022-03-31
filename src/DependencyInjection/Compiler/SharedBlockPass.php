<?php

namespace WebEtDesign\CmsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WebEtDesign\CmsBundle\Factory\SharedBlockFactory;
use WebEtDesign\CmsBundle\Factory\PageFactory;
use WebEtDesign\CmsBundle\Manager\PageTemplateManager;

class SharedBlockPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        $config = [];

        foreach ($container->findTaggedServiceIds('wd_cms.shared_block') as $id => $tags) {
            $definition = $container->findDefinition($id);

            $definition->setPublic(true);

            foreach ($tags as $tag) {
                $config[$tag['key']] = array_filter(['code' => $tag['key']]);
            }
        }

        $templateFactory = $container->getDefinition(SharedBlockFactory::class);
        $templateFactory->setArgument(1, $config);

    }
}
