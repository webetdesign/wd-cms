<?php

namespace WebEtDesign\CmsBundle\Utils;

use Sonata\AdminBundle\Form\Type\ModelListType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\MediaBundle\Form\Type\WDMediaType;

/**
 * Trait SmoOpenGraphAdminTrait
 * @package WebEtDesign\CmsBundle\Utils
 * @deprecated Use the trait in wd-seo-bundle
 */
trait SmoOpenGraphAdminTrait
{
    public function addFormFieldSmoOpenGraph($formMapper)
    {
        $formMapper
            ->with('cms_page.form.seo.open_graph',
                ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('og_title', TextType::class, [
                'label'    => 'cms_page.form.og_title.label',
                'required' => false

            ])
            ->add('og_type', TextType::class, [
                'label'    => 'cms_page.form.og_type.label',
                'required' => false

            ])
            ->add('og_description', TextareaType::class, [
                'label'    => 'cms_page.form.og_description.label',
                'required' => false

            ])
            ->add('og_site_name', TextType::class, [
                'label'    => 'cms_page.form.og_site_name.label',
                'required' => false

            ])
            ->add('og_image', WDMediaType::class, [
                'category' => 'SEO',
                'required' => false
            ])
            ->end();
    }

    public function addShowFieldSmoOpenGraph($formMapper)
    {
        $formMapper
            ->with('cms_page.form.seo.open_graph',
                ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('og_title', null, ['label' => 'cms_page.form.og_title.label'])
            ->add('og_type', null, ['label' => 'cms_page.form.og_type.label'])
            ->add('og_url', null, ['label' => 'cms_page.form.og_url.label'])
            ->add('og_image', null, ['label' => 'cms_page.form.og_image.label'])
            ->add('og_description', null, ['label' => 'cms_page.form.og_description.label'])
            ->add('og_site_name', null, ['label' => 'cms_page.form.og_site_name.label'])
            ->add('og_admins', null, ['label' => 'cms_page.form.og_admins.label'])
            ->end();
    }
}
