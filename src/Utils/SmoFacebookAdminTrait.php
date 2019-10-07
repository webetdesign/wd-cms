<?php

namespace WebEtDesign\CmsBundle\Utils;

trait SmoFacebookAdminTrait
{
    public function addFormFieldSmoFacebook($formMapper)
    {
        $formMapper
            ->with('Facebook', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('fb_title')
            ->add('fb_type')
            ->add('fb_url')
            ->add('fb_image')
            ->add('fb_description')
            ->add('fb_site_name')
            ->add('fb_admins')
            ->end();
    }

    public function addShowFieldSmoFacebook($formMapper)
    {
        $formMapper
            ->with('Facebook', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('fb_title')
            ->add('fb_type')
            ->add('fb_url')
            ->add('fb_image')
            ->add('fb_description')
            ->add('fb_site_name')
            ->add('fb_admins')
            ->end();
    }
}
