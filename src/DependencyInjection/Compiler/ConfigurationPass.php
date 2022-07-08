<?php

namespace WebEtDesign\CmsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WebEtDesign\CmsBundle\Factory\PageFactory;
use WebEtDesign\CmsBundle\Factory\SharedBlockFactory;
use WebEtDesign\CmsBundle\Factory\TemplateFactoryInterface;
use WebEtDesign\CmsBundle\Manager\BlockFormThemesManager;

class ConfigurationPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        $pageFactory = $container->getDefinition(PageFactory::class);
        $sharedBlockFactory = $container->getDefinition(SharedBlockFactory::class);

        foreach ($container->findTaggedServiceIds('wd_cms.configuration') as $id => $tags) {
            $definition = $container->findDefinition($id);
            $definition->setPublic(true);

            $pageFactory->addMethodCall('setConfiguration', [$definition]);
            $sharedBlockFactory->addMethodCall('setConfiguration', [$definition]);
        }
    }
}
