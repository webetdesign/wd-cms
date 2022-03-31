<?php

namespace WebEtDesign\CmsBundle\Form;

use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use WebEtDesign\CmsBundle\Factory\SharedBlockFactory;
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
    public function __construct(private SharedBlockFactory $sharedBlockFactory) {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [];
        foreach ($this->sharedBlockFactory->getTemplateList($options['collection']) as $value => $tpl) {
            $choices[$tpl->getLabel()] = $value;
        }

        $builder->add('tpl', ChoiceType::class, [
            'label'        => false,
            'choices'      => $choices,
        ]);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                return ['tpl' => $value];
            },
            function ($value) {
                return $value['tpl'] ?? null;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'collection' => null,
        ]);
    }
}
