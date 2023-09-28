<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\DependencyInjection\Compiler;

use App\Cms\Configuration;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

class TemplatePass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void
    {
        $templateConfig = [];

        foreach ($container->findTaggedServiceIds('wd_cms.template') as $id => $tags) {
            foreach ($tags as $tag) {
                $templateConfig[$tag['key']] = [
                    'id'   => $id,
                    'code' => $tag['key'],
                    'type' => $tag['type'],
                ];
            }
        }

        $templateRegistry = $container->getDefinition(TemplateRegistry::class);
        $templateRegistry->setArgument(1, $templateConfig);
    }
}
