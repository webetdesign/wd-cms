<?php

namespace WebEtDesign\CmsBundle\Form\Transformer;

use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Form\DataTransformerInterface;

class CmsBlockTransformer implements DataTransformerInterface
{

    /**
     * @var false|mixed
     */
    protected bool                 $deep = false;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function transform($value, $deep = false)
    {
        $this->deep = $deep;
        if (is_string($value) && is_array(json_decode($value, true))) {
            $value = json_decode($value, true);
        }

        if (is_array($value)) {
            $value = $this->transformObject($value);
            if (is_array($value)) {
                $value = $this->transformArray($value);
            }
        }


        return $value;
    }

    public function reverseTransform($value)
    {
        if (is_array($value)) {
            $value = $this->reverseTransformArray($value);
        }

        if (is_object($value)) {
            $value = $this->reverseTransformObject($value);
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    private function transformObject(array $value): mixed
    {
        if (isset($value['id']) && isset($value['class'])) {
            return $this->em->find($value['class'], $value['id']);
        }
        return $value;
    }

    #[ArrayShape(['id' => "", 'class' => "string"])]
    private function reverseTransformObject(mixed $value): array
    {
        return [
            'id'    => $value->getId(),
            'class' => get_class($value)
        ];
    }

    private function transformArray(array $value): array
    {
        $array = [];
        foreach ($value as $index => $item) {
            if ($this->deep && is_string($item) && is_array(json_decode($item, true))) {
                $item = json_decode($item, true);
            }
            if (is_array($item)) {
                $item = $this->transformObject($item);

                if (is_array($item)) {
                    $item = $this->transformArray($item);
                }
            }
            $array[$index] = $item;
        }
        return $array;
    }

    private function reverseTransformArray(mixed $value)
    {
        $array = [];
        foreach ($value as $index => $item) {
            if (is_object($item)) {
                $array[$index] = $this->reverseTransformObject($item);
            } elseif (is_array($item)) {
                $array[$index] = $this->reverseTransformArray($item);
            } else {
                $array[$index] = $item;
            }

        }
        return $array;
    }
}
