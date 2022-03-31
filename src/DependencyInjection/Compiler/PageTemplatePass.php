<?php

namespace WebEtDesign\CmsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WebEtDesign\CmsBundle\Factory\PageFactory;

class PageTemplatePass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        $templateConfig            = [];

        foreach ($container->findTaggedServiceIds('wd_cms.page_template') as $id => $tags) {
            $definition = $container->findDefinition($id);

            $definition->setPublic(true);

            foreach ($tags as $tag) {
                $templateConfig[$tag['key']] = array_filter(['code' => $tag['key']]);
            }
        }

        $templateFactory = $container->getDefinition(PageFactory::class);
        $templateFactory->setArgument(1, $templateConfig);



    }
}
