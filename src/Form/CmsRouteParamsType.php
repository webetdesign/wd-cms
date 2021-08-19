<?php


namespace WebEtDesign\CmsBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use WebEtDesign\CmsBundle\Entity\CmsRoute;

class CmsRouteParamsType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;
    private   $cmsConfig;

    /**
     * @inheritDoc
     */
    public function __construct(EntityManagerInterface $em, $cmsConfig)
    {
        $this->em        = $em;
        $this->cmsConfig = $cmsConfig;
    }


    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $options['config'];
        /** @var CmsRoute $route */
        $route  = $options['route'];
        $object = $options['object'];
        $locale = $object->getPage()->getSite()->getLocale();

        $defaults     = json_decode($route->getDefaults(), true);
        $requirements = json_decode($route->getRequirements(), true);

        foreach ($route->getParams() as $name) {
            $param = $config['params'][$name] ?? null;
            $type  = !empty($param['entity']) ? EntityType::class : TextType::class;
            $opts  = !empty($param['entity']) ? [
                'class'        => $param['entity'],
                'choice_value' => function ($entity = null) use ($param, $locale) {
                    $getter = 'get' . ucfirst($param['property']);
                    if ($this->cmsConfig['multilingual'] == true && is_subclass_of($entity, TranslatableInterface::class)) {
                        return $entity ? $entity->translate($locale)->$getter() : '';
                    } else {
                        return $entity ? $entity->$getter() : '';
                    }
                },
                'required'     => false
            ] : [
                'required' => false
            ];

            if (isset($defaults[$name])) {
                if (empty($param['entity'])) {
                    $opts['empty_data']          = $defaults[$name];
                    $opts['attr']['placeholder'] = $defaults[$name];
                } else {
                    $opts['choice_attr'] = function ($choice, $key, $value) use ($defaults, $name) {
                        $attr = [];
                        if ($value == $defaults[$name]) {
                            $attr['selected'] = 'selected';
                        }
                        return $attr;
                    };
                }
            }
            if (isset($requirements[$name]) && !empty($requirements[$name])) {
                $opts['constraints'][] = new Regex([
                    'pattern' => '/' . $requirements[$name] . '/',
                    'match'   => true,
                ]);

                if (!preg_match('/' . $requirements[$name] . '/', '')) {
                    $opts['constraints'][] = new NotBlank();
                }
            }

            $builder->add($name, $type, $opts);
        }

        $builder->addModelTransformer(new CallbackTransformer(
            function ($values) use ($config, $object) {
                if ($values != null) {
                    $values = json_decode($values, true);
                    foreach ($values as $name => $value) {
                        $param = $config['params'][$name] ?? null;
                        if ($param && isset($param['entity']) && isset($param['property'])) {
                            if ($this->cmsConfig['multilingual'] == true && is_subclass_of($param['entity'], TranslatableInterface::class)) {
                                $method = 'findOneBy'.ucfirst($param['property']);
                                $locale = $object->getPage()->getSite()->getLocale();
                                $entity = $this->em->getRepository($param['entity'])->$method($value, $locale);
                            } else {
                                $entity = $this->em->getRepository($param['entity'])->findOneBy([$param['property'] => $value]);
                            }
                            $values[$name] = $entity ?? null;
                        }
                    }
                }
                return $values;
            },
            function ($values) use ($config, $locale) {
                foreach ($values as $name => $value) {
                    $param = $config['params'][$name] ?? null;
                    if ($param && isset($param['property'])) {
                        $getter = 'get' . ucfirst($param['property']);
                        if (method_exists($value, $getter)) {
                            if ($this->cmsConfig['multilingual'] == true && is_subclass_of($value, TranslatableInterface::class)) {
                                $values[$name] = $value->translate($locale)->$getter();
                            } else {
                                $values[$name] = $value->$getter();
                            }
                        }
                    }
                }
                return json_encode($values);
            }
        ));
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('config');
        $resolver->setRequired('route');
        $resolver->setRequired('object');
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'cms_route_params';
    }


}
