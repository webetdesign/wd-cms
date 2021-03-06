<?php

namespace WebEtDesign\CmsBundle\Utils;

use Sonata\AdminBundle\Form\Type\ModelListType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\MediaBundle\Form\Type\WDMediaType;

trait SmoTwitterAdminTrait
{
    public function addFormFieldSmoTwitter($formMapper)
    {
        $formMapper
            ->with('cms_page.form.seo.twitter',
                ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('twitter_card', TextType::class, [
                'label'    => 'cms_page.form.twitter_card.label',
                'required' => false

            ])
            ->add('twitter_site', TextType::class, [
                'label'    => 'cms_page.form.twitter_site.label',
                'required' => false

            ])
            ->add('twitter_title', TextType::class, [
                'label'    => 'cms_page.form.twitter_title.label',
                'required' => false

            ])
            ->add('twitter_description', TextareaType::class, [
                'label'    => 'cms_page.form.twitter_description.label',
                'required' => false

            ])
            ->add('twitter_creator', TextType::class, [
                'label'    => 'cms_page.form.twitter_creator.label',
                'required' => false

            ])
            ->add('twitter_image', WDMediaType::class, [
                'category' => 'SEO',
                'required' => false
            ])
            ->end();
    }

    public function addShowFieldSmoTwitter($formMapper)
    {
        $formMapper
            ->with('Twitter', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('twitter_card', null, ['label' => 'cms_page.form.twitter_card.label'])
            ->add('twitter_site', null, ['label' => 'cms_page.form.twitter_site.label'])
            ->add('twitter_title', null, ['label' => 'cms_page.form.twitter_title.label'])
            ->add('twitter_description', null,
                ['label' => 'cms_page.form.twitter_description.label'])
            ->add('twitter_creator', null, ['label' => 'cms_page.form.twitter_creator.label'])
            ->add('twitter_image', null, ['label' => 'cms_page.form.twitter_image.label'])
            ->end();
    }
}
