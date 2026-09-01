<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use Symfony\Component\Form\AbstractType;
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
        $this->em        = $em;
        $this->pageClass = $pageClass;
        $this->siteClass = $siteClass;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $site        = $options['site'];
        $this->sites = $this->em->getRepository($this->siteClass)->findOther($site, $options['templateFilter']);

        /** @var CmsSite $s */
        foreach ($this->sites as $s) {
            $root = $s->getRootPage();
            $builder->add((string)$s->getId(), EntityType::class, [
                'required'      => false,
                'label'         => $s->getLabel(),
                'class'         => $this->pageClass,
                'query_builder' => function (EntityRepository $er) use ($root) {
                    return $er->createQueryBuilder('p')
                        ->join('p.root', 'r')
                        ->where('r = :root')
                        ->setParameter('root', $root);
                },
            ]);
        }

    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {

        $view->vars['sites'] = $this->sites;

        parent::buildView($view, $form, $options);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'page'           => null,
                'site'           => null,
                'compound'       => true,
                'templateFilter' => null
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'cms_multilingual_page';
    }

    public function getParent(): string
    {
        return ImmutableArrayType::class;
    }


}
