<?php

namespace WebEtDesign\CmsBundle\Utils;

use WebEtDesign\CmsBundle\Form\GlobalVarsType;

trait GlobalVarsAdminTrait
{
    public function addGlobalVarsHelp($formMapper, $page, $show = false, $side = false)
    {
        if ($show) {
            $formMapper
                ->with('Variables disponibles dans les champs :', ['box_class' => 'box box-primary header_none', 'class' => $side ? 'col-xs-3' : 'col-xs-12'])
                ->add(uniqid(), GlobalVarsType::class, ['mapped' => false, "page" => $page, 'label' => 'Variables disponibles dans les champs :', 'required' => false])
                ->end();
        }
    }
}
