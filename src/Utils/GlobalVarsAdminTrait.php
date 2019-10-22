<?php

namespace WebEtDesign\CmsBundle\Utils;

use WebEtDesign\CmsBundle\Form\GlobalVarsType;

trait GlobalVarsAdminTrait
{
    public function addGlobalVarsHelp($formMapper, $page, $show = false)
    {
        if ($show) {
            $formMapper
                ->with('Variable disponible dans les champs :', ['class' => 'col-xs-12'])
                ->add(uniqid(), GlobalVarsType::class, ['mapped' => false, "page" => $page, 'label' => false])
                ->end();
        }
    }
}
