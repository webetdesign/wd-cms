<?php

namespace WebEtDesign\CmsBundle\Utils;

trait SmoTwitterAdminTrait
{
    public function addFormFieldSmoTwitter($formMapper)
    {
        $formMapper
            ->with('Twitter', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('twitter_card')
            ->add('twitter_site')
            ->add('twitter_title')
            ->add('twitter_description')
            ->add('twitter_creator')
            ->add('twitter_image')
            ->end();
    }

    public function addShowFieldSmoTwitter($formMapper)
    {
        $formMapper
            ->with('Twitter', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('twitter_card')
            ->add('twitter_site')
            ->add('twitter_title')
            ->add('twitter_description')
            ->add('twitter_creator')
            ->add('twitter_image')
            ->end();
    }
}
