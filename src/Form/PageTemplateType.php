<?php

namespace WebEtDesign\CmsBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use WebEtDesign\CmsBundle\Services\TemplateProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TemplateType
 * @package App\Form
 *
 * Type of template defined in wd_cms.yaml
 */
class PageTemplateType extends AbstractType
{
    private $provider;

    public function __construct(TemplateProvider $provider)
    {
        $this->provider = $provider;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => $this->provider->getTemplateList(),
            ]
        );
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @return TemplateProvider
     */
    public function getProvider(): TemplateProvider
    {
        return $this->provider;
    }
}
