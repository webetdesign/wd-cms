<?php

namespace WebEtDesign\CmsBundle\Vars;


use WebEtDesign\CmsBundle\CMS\Configuration\VarDefinition;
use WebEtDesign\CmsBundle\Enum\CmsVarsDelimiterEnum;

class CmsVarsBag
{
    public function __construct(private readonly Compiler $compiler) { }

    /**
     * @var array|VarDefinition[]
     */
    protected array $definitions = [];
    protected array $objects = [];

    public function configure(VarDefinition $definition): static
    {
        $this->definitions[] = $definition;
        return $this;
    }

    public function getExposed(): array
    {
        return $this->compiler->exposed($this->getDefinitions());
    }

    public function compile(): array
    {
        return $this->compiler->compile($this->getDefinitions(), $this->getObjects());
    }

    public function replaceIn(string $value): string
    {
        foreach ($this->compile() as $var => $v) {
            $value = str_replace($var, $v, $value);
        }

        return $value;
    }

    /**
     * @param string $code Code must match with VarDefinition->code
     * @param object $object Object to register
     * @return $this
     */
    public function populate(string $code, object $object): static
    {
        $this->objects[$code] = $object;
        return $this;
    }

    /**
     * @return array
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @param array $definitions
     * @return CmsVarsBag
     */
    public function setDefinitions(array $definitions): static
    {
        $this->definitions = $definitions;
        return $this;
    }

    /**
     * @return array
     */
    public function getObjects(): array
    {
        return $this->objects;
    }

    /**
     * @param array $objects
     * @return CmsVarsBag
     */
    public function setObjects(array $objects): CmsVarsBag
    {
        $this->objects = $objects;
        return $this;
    }

    /**
     * @param CmsVarsDelimiterEnum $delimiterEnum
     * @return CmsVarsBag
     */
    public function setDelimiterEnum(CmsVarsDelimiterEnum $delimiterEnum): CmsVarsBag
    {
        $this->delimiterEnum = $delimiterEnum;
        return $this;
    }
}
