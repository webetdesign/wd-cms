<?php

namespace WebEtDesign\CmsBundle\Form;

use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

/**
 * Class TemplateType
 * @package App\Form
 *
 * Type of template defined in wd_cms.yaml
 */
class BlockTemplateType extends AbstractType
{
    public function __construct(private readonly TemplateRegistry $templateRegistry) { }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tpl', ChoiceType::class, [
            'required'    => false,
            'label'       => false,
            'choices'     => $this->templateRegistry->getChoiceList(TemplateRegistry::TYPE_SHARED, $options['collection']),
            'constraints' => [
                new NotBlank(),
            ]
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
