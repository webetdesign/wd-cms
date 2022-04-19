<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 2019-10-08
 * Time: 10:32
 */

namespace WebEtDesign\CmsBundle\Services;

use JetBrains\PhpStorm\ArrayShape;
use WebEtDesign\CmsBundle\Entity\CmsGlobalVarsDelimiterEnum;
use WebEtDesign\CmsBundle\Entity\GlobalVarsInterface;

class AbstractCmsGlobalVars implements GlobalVarsInterface
{
    /** @var GlobalVarsInterface */
    protected $object;

    protected $delimiter;

    public static function getAvailableVars(): array {
        return [];
    }

    public function getMethod($object, $name)
    {
        switch (true) {
            case method_exists($object, 'get'.ucfirst($name)):
                return 'get'.ucfirst($name);
            case method_exists($object, 'is'.ucfirst($name)):
                return 'is'.ucfirst($name);
            case method_exists($object, $name):
                return $name;
        }
        return false;
    }

    public function computeValues()
    {
        $vars = $this::getAvailableVars();
        $values = [];
        foreach ($vars as $var) {
            if ($method = $this->getMethod($this, $var)) {
                $values[$var] = $this->$method();
            }
        }

        if ($this->object) {
            $objectVars = $this->object::getAvailableVars();
            foreach ($objectVars as $var) {
                if ($method = $this->getMethod($this->object, $var)) {
                    $values[$var] = $this->object->$method();
                }
            }
        }

        return $values;
    }

    public function replaceVars($str)
    {
        $values = $this->computeValues();

        $d = $this->getDelimiters();

        foreach ($values as $name => $value) {
            $str = str_replace($d['s'] . $name . $d['e'], $value, $str);
        }

        return $str;
    }

    /**
     * @param mixed $object
     * @return AbstractCmsGlobalVars
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return mixed
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    #[ArrayShape(['s' => "string", 'e' => "string"])] public function getDelimiters()
    {
        switch ($this->delimiter) {
            case CmsGlobalVarsDelimiterEnum::DOUBLE_UNDERSCORE:
                $de = $ds = '__';
                break;
            case CmsGlobalVarsDelimiterEnum::DOUBLE_SQUARE_BRACKETS:
                $ds = '[[';
                $de = ']]';
                break;
            case CmsGlobalVarsDelimiterEnum::SQUARE_BRACKETS:
                $ds = '[';
                $de = ']';
                break;
        }

        return ['s' => $ds, 'e' => $de];
    }

    /**
     * @param mixed $delimiter
     */
    public function setDelimiter($delimiter): void
    {
        $this->delimiter = $delimiter;
    }
}
