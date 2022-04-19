<?php

namespace WebEtDesign\CmsBundle\Services;


use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;

class EntityContent extends AbstractCustomContent
{
    protected $multiple;
    protected $entity;
    protected $em;

    /**
     * @param EntityManagerInterface $em
     * @param $entity
     * @param $multiple
     */
    public function __construct($em, $entity, $multiple)
    {
        $this->em       = $em;
        $this->entity   = $entity;
        $this->multiple = $multiple;
    }

    public function getFormType(): string
    {
        return EntityType::class;
    }

    #[ArrayShape(['class'           => "",
                  'required'        => "false",
                  'auto_initialize' => "false",
                  'multiple'        => ""
    ])] public function getFormOptions(): array
    {
        return [
            'class'           => $this->entity,
            'required'        => false,
            'auto_initialize' => false,
            'multiple'        => $this->multiple,
        ];
    }

    public function getCallbackTransformer(): DataTransformerInterface
    {
        return new CallbackTransformer(
            function ($value) {
                if ($this->multiple) {
                    $objects = $this->em->getRepository($this->entity)->findBy(['id' => json_decode($value)]);
                    return $objects;
                } else {
                    return $this->em->getRepository($this->entity)->find((int)$value);
                }
            },
            function ($value) {
                if ($this->multiple) {
                    $ids = [];
                    if (is_array($value)){
                        foreach ($value as $object) {
                            $ids[] = $object->getId();
                        }
                    }
                    return json_encode($ids);
                } else {
                    return $value !== null ? $value->getId() : null;
                }
            }
        );
    }

    public function render(CmsContent $content)
    {
        if ($this->multiple) {
            $objects = $this->em->getRepository($this->entity)->findBy(['id' => json_decode($content->getValue())]);
            return $objects;
        } else {
            return $this->em->getRepository($this->entity)->find((int)$content->getValue());
        }
    }

}
