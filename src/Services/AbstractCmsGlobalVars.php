<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 2019-10-08
 * Time: 10:32
 */

namespace WebEtDesign\CmsBundle\Services;

use WebEtDesign\CmsBundle\Entity\CmsGlobalVarsDelimiterEnum;
use WebEtDesign\CmsBundle\Entity\GlobalVarsInterface;

abstract class AbstractCmsGlobalVars implements GlobalVarsInterface
{
    /** @var GlobalVarsInterface */
    protected $object;

    protected $delimiter;

    abstract public static function getAvailableVars(): array;

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

    public function computeValues($service)
    {
        $vars = $service::getAvailableVars();
        $values = [];
        foreach ($vars as $var) {
            if ($method = $this->getMethod($service, $var)) {
                $values[$var] = $service->$method();
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

    public function getDelimiters()
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
