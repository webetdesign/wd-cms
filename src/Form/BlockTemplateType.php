<?php

namespace WebEtDesign\CmsBundle\Form;

use WebEtDesign\CmsBundle\Services\PageProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TemplateType
 * @package App\Form
 *
 * Type of template defined in wd_cms.yaml
 */
class BlockTemplateType extends AbstractType
{
    private $provider;

    public function __construct(PageProvider $provider)
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
}
