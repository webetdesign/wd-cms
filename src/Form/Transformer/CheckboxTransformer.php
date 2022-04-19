<?php

namespace WebEtDesign\CmsBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

class CheckboxTransformer implements DataTransformerInterface
{

    public function transform($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public function reverseTransform($value): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOL) ? 1 : 0;
    }
}
