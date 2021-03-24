<?php

namespace WebEtDesign\CmsBundle\Utils;

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Trait SmoFacebookAdminTrait
 * @package WebEtDesign\CmsBundle\Utils
 *
 * @deprecated(use WebEtDesign\CmsBundle\Utils\SmoOpenGraphAdminTrait instead)
 */
trait SmoFacebookAdminTrait
{
    public function addFormFieldSmoFacebook($formMapper)
    {
        $formMapper
            ->with('cms_page.form.seo.open_graph', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('fb_title', TextType::class, ['label' => 'cms_page.form.fb_title.label'])
            ->add('fb_type', TextType::class, ['label' => 'cms_page.form.fb_type.label'])
            ->add('fb_url', TextType::class, ['label' => 'cms_page.form.fb_url.label'])
            ->add('fb_image', TextType::class, ['label' => 'cms_page.form.fb_image.label'])
            ->add('fb_description', TextType::class, ['label' => 'cms_page.form.fb_description.label'])
            ->add('fb_site_name', TextType::class, ['label' => 'cms_page.form.fb_site_name.label'])
            ->add('fb_admins', TextType::class, ['label' => 'cms_page.form.fb_admins.label'])
            ->end();
    }

    public function addShowFieldSmoFacebook($formMapper)
    {
        $formMapper
            ->with('cms_page.form.seo.open_graph', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('fb_title', null, ['label' => 'cms_page.form.fb_title.label'])
            ->add('fb_type', null, ['label' => 'cms_page.form.fb_type.label'])
            ->add('fb_url', null, ['label' => 'cms_page.form.fb_url.label'])
            ->add('fb_image', null, ['label' => 'cms_page.form.fb_image.label'])
            ->add('fb_description', null, ['label' => 'cms_page.form.fb_description.label'])
            ->add('fb_site_name', null, ['label' => 'cms_page.form.fb_site_name.label'])
            ->add('fb_admins', null, ['label' => 'cms_page.form.fb_admins.label'])
            ->end();
    }
}
