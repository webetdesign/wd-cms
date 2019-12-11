<?php


namespace WebEtDesign\CmsBundle\Form;


use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * @inheritDoc
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
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

        $defaults     = json_decode($route->getDefaults(), true);
        $requirements = json_decode($route->getRequirements(), true);


        foreach ($route->getParams() as $name) {
            $param = $config['params'][$name] ?? null;
            $type  = !empty($param['entity']) ? EntityType::class : TextType::class;
            $opts  = !empty($param['entity']) ? [
                'class'        => $param['entity'],
                'choice_value' => function ($entity = null) use ($param) {
                    $getter = 'get' . ucfirst($param['property']);

                    return $entity ? $entity->$getter() : '';
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
                $opts['constraints'] = [
                    new Regex([
                        'pattern' => '/' . $requirements[$name] . '/',
                        'match'   => true,
                    ])
                ];

                if (!preg_match('/' . $requirements[$name] . '/', '')) {
                    $opts['constraints'] = [
                        new NotBlank()
                    ];
                }
            }

            $builder->add($name, $type, $opts);
        }

        $builder->addModelTransformer(new CallbackTransformer(
            function ($values) use ($config) {
                if ($values != null) {
                    $values = json_decode($values, true);
                    foreach ($values as $name => $value) {
                        $param = $config['params'][$name] ?? null;
                        if ($param && isset($param['entity']) && isset($param['property'])) {
                            $object        = $this->em->getRepository($param['entity'])->findOneBy([$param['property'] => $value]);
                            $values[$name] = $object;
                        }
                    }
                }

                return $values;
            },
            function ($values) use ($config) {
                foreach ($values as $name => $value) {
                    $param = $config['params'][$name] ?? null;
                    if ($param && isset($param['property'])) {
                        $getter = 'get' . ucfirst($param['property']);
                        if (method_exists($value, $getter)) {
                            $values[$name] = $value->$getter();
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
