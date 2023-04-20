<?php

namespace WebEtDesign\CmsBundle\Utils;

use Sonata\AdminBundle\Form\FormMapper;
use WebEtDesign\CmsBundle\Form\Admin\CmsVarsFormSection;

trait CmsVarsAdminTrait
{
    public function addFormVarsSection(FormMapper $form, $object, $key): void
    {
        $form->with('vars_' . $key, ['box_class' => 'header_none']);
        $form->add('cms_vars_' . $key, CmsVarsFormSection::class, ['template' => $object->getTemplate()]);
        $form->end();
    }
}
