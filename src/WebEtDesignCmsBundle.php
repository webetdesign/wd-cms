<?php

namespace WebEtDesign\CmsBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use WebEtDesign\CmsBundle\DependencyInjection\CmsExtension;

/**
 * References:
 * @link http://symfony.com/doc/current/book/bundles.html
 */
class WebEtDesignCmsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $extension = new CmsExtension();
        $extension->load();
    }



}
