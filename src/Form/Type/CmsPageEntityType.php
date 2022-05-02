<?php

namespace WebEtDesign\CmsBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;

class CmsPageEntityType extends AbstractType
{
    public function __construct(private CmsPageRepository $cmsPageRepository) { }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $collections = $options['collections'];


        $builder->add('page', EntityType::class, [
            'required'      => $options['required'],
            'class'         => CmsPage::class,
            'label'         => false,
            'query_builder' => $this->cmsPageRepository->getBuilderByCollections($collections),
            'choice_label'  => function (CmsPage $page) {
                return str_repeat('—', $page->getLvl()) . ' ' . $page->getTitle();
            },
            'group_by'      => function ($choice, $key, $value) {
                return $choice->getSite()->__toString();
            },
        ]);

        $builder->addModelTransformer(new CallbackTransformer(
            fn($value) => ['page' => $value],
            fn($value) => $value['page']
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'collections' => null
        ]);
    }
}
