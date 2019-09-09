<?php

namespace WebEtDesign\CmsBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TemplateType
 * @package App\Form
 *
 * Type of template defined in wd_cms.yaml
 */
class MultilingualType extends AbstractType
{
    protected $sites;
    protected $pageClass;
    protected $siteClass;
    private   $em;


    public function __construct(EntityManager $em, $pageClass, $siteClass)
    {
        $this->em = $em;
        $this->pageClass = $pageClass;
        $this->siteClass = $siteClass;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $site        = $options['site'];
        $this->sites = $this->em->getRepository($this->siteClass)->findOther($site);

        /** @var CmsSite $s */
        foreach ($this->sites as $s) {
            $builder->add($s->getId(), EntityType::class, [
                'required'      => false,
                'label'         => $s->getLabel(),
                'class'         => $this->pageClass,
                'query_builder' => function (EntityRepository $er) use ($s) {
                    return $er->createQueryBuilder('p')
                        ->where('p.site = :site')
                        ->setParameter('site', $s);
                },
            ]);
        }

    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {

        $view->vars['sites'] = $this->sites;

        parent::buildView($view, $form, $options);
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'page'     => null,
                'site'     => null,
                'compound' => true
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'cms_multilingual_page';
    }

    public function getParent()
    {
        return ImmutableArrayType::class;
    }


}
