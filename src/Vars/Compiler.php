<?php

namespace WebEtDesign\CmsBundle\Vars;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use WebEtDesign\CmsBundle\Attribute\AsCmsVarsObject;
use WebEtDesign\CmsBundle\Attribute\AsCmsVarsProperty;
use WebEtDesign\CmsBundle\CMS\Configuration\VarDefinition;
use WebEtDesign\CmsBundle\Enum\CmsVarsDelimiterEnum;

class Compiler
{
    protected CmsVarsDelimiterEnum $delimiter = CmsVarsDelimiterEnum::DOUBLE_UNDERSCORE;

    /**
     * @param CmsVarsDelimiterEnum|null $delimiter
     */
    public function __construct(?CmsVarsDelimiterEnum $delimiter = null)
    {
        if ($delimiter !== null) {
            $this->delimiter = $delimiter;
        }
    }


    /**
     * @param array|VarDefinition[] $vars
     * @return array
     */
    public function exposed(array $vars): array
    {
        $exposed = [];

        foreach ($vars as $var) {
            try {
                $reflectionClass = new ReflectionClass($var->getClass());
                $code            = $var->getCode();
                $baseName        = $var->getName() ?: $var->getCode();

                $exposed[$code] = $this->parseClass($reflectionClass, $baseName);
            } catch (ReflectionException $e) {
            }
        }

        return $exposed;
    }

    private function parseClass(ReflectionClass $reflectionClass, string $baseName, ?string $baseProp = null): array
    {
        $exposed = [];

        foreach ([...$reflectionClass->getProperties(), ...$reflectionClass->getMethods()] as $reflectionProperty) {
            $propertyAttributes = $reflectionProperty->getAttributes(AsCmsVarsProperty::class);
            $objectAttributes   = $reflectionProperty->getAttributes(AsCmsVarsObject::class);
            if (!empty($propertyAttributes)) {
                $name = $propertyAttributes[0]->getArguments()['name'] ?? $reflectionProperty->getName();

                $key = $this->delimiter->start() . $baseName . '.' . $name . $this->delimiter->end();

                if ($baseProp) {
                    $exposed[$key] = $baseProp . '.' . $reflectionProperty->getName();
                } else {
                    $exposed[$key] = $reflectionProperty->getName();
                }
            }

            if (!empty($objectAttributes)) {
                $name = $objectAttributes[0]->getArguments()['name'] ?? $reflectionProperty->getName();

                try {
                    $subObjectReflection = new ReflectionClass($reflectionProperty->getType()->getName());
                    $subExposed          = $this->parseClass($subObjectReflection, $baseName . '.' . $name, $reflectionProperty->getName());
                    $exposed             = array_merge($exposed, $subExposed);
                } catch (ReflectionException $e) {
                }
            }
        }

        return $exposed;
    }

    /**
     * @param array|VarDefinition[] $vars
     * @param array $objects
     * @return array
     */
    public function compile(array $vars, array $objects): array
    {
        $exposed = $this->exposed($vars);

        $compiled = [];

        foreach ($exposed as $code => $keys) {
            $object = $objects[$code] ?? null;

            if (!$object) {
                continue;
            }

            foreach ($keys as $key => $prop) {
                $compiled[$key] = $this->compileProp($object, $prop);
            }
        }

        return $compiled;
    }

    private function compileProp($object, $prop)
    {
        $accessor = new PropertyAccessor();
        $accessor->getValue($object, $prop);
        $value = $accessor->getValue($object, $prop);

        if (is_object($value)) {
            $value = $value->__toString();
        }

        return $value;
    }

}
