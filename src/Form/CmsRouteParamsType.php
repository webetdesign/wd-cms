<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use WebEtDesign\CmsBundle\CMS\Template\PageInterface;
use WebEtDesign\CmsBundle\Entity\CmsRoute;

class CmsRouteParamsType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $em;
    private array                    $cmsConfig;

    public function __construct(EntityManagerInterface $em, $cmsConfig)
    {
        $this->em        = $em;
        $this->cmsConfig = $cmsConfig;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PageInterface config */
        $config      = $options['config'];
        $routeConfig = $config->getRoute();

        /** @var CmsRoute $route */
        $route  = $options['route'];
        $object = $options['object'];
        $locale = $object->getPage()->getSite()->getLocale();

        $defaults     = json_decode($route->getDefaults(), true);
        $requirements = json_decode($route->getRequirements(), true);

        foreach ($route->getParams() as $name) {
            $attribute = $routeConfig->getAttribute($name);

            if (isset($requirements[$name]) && !empty($requirements[$name])) {
                $constraints = [
                    new Regex([
                        'pattern' => '/' . $requirements[$name] . '/',
                    ])
                ];
            }
            if (!empty($attribute->getFormType())) {
                $builder->add($name, $attribute->getFormType(), [
                    'required'    => false,
                    'constraints' => $constraints ?? [],
                ]);
            } elseif (!empty($attribute->getEntityClass())) {
                $choices = [];
                foreach ($this->em->getRepository($attribute->getEntityClass())->findAll() as $item) {
                    if (!empty($attribute->getEntityProperty())) {
                        $getter = 'get' . ucfirst($attribute->getEntityProperty());
                        if ($this->cmsConfig['multilingual'] &&
                            is_subclass_of($attribute->getEntityClass(), TranslatableInterface::class)) {
                            $choices[$item->__toString()] = $item->translate($locale)->$getter();
                        } else {
                            $choices[$item->__toString()] = $item->$getter();
                        }
                    } else {
                        $choices[$item->__toString()] = $item->getId();
                    }
                }

                $builder->add($name, ChoiceType::class, [
                    'choices'     => $choices,
                    'required'    => false,
                    'constraints' => $constraints ?? [],
                ]);
            } else {
                $builder->add($name, TextType::class, [
                    'constraints' => $constraints ?? [],
                    'required'    => false,
                ]);
            }
        }

//        $builder->addModelTransformer(new CallbackTransformer(
//            function ($values) use ($config, $object) {
//                if ($values != null) {
//                    $values = json_decode($values, true);
//                }
//                return $values;
//            },
//            function ($values) use ($config, $locale) {
//                return json_encode($values);
//            }
//        ));
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('config');
        $resolver->setRequired('route');
        $resolver->setRequired('object');
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix(): string
    {
        return 'cms_route_params';
    }

}
