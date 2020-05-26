<?php


namespace WebEtDesign\CmsBundle\Services;


use Knp\Menu\ItemInterface;

interface CmsMenuBuilderInterface
{

    public function build(ItemInterface $node, $locale);

}
