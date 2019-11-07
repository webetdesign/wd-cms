<?php

namespace WebEtDesign\CmsBundle\Services;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class PageProvider
 * @package WebEtDesign\CmsBundle\Services
 * Provide the configuration set in wd_cms.yaml
 */
class TemplateProvider
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param null $filter
     * @return array
     *
     * List of available templates for choice type
     */
    public function getTemplateList($filter = null)
    {
        $list = [];

        foreach ($this->config as $key => $template) {
            if ($filter) {
                if (preg_match('/'.$filter.'/', $key)) {
                    $list[$template['label']] = $key;
                }
                if (preg_match('/common/', $key)) {
                    $list[$template['label']] = $key;
                }
            } else {
                $list[$template['label']] = $key;
            }

        }

        return $list;
    }

    /**
     * @param $templateName
     * @return mixed
     * @throws \Exception
     *
     * Retrieve a twig path for a template
     */
    public function getTemplate($templateName)
    {
        if (!isset($this->config[$templateName])) {
            throw new \Exception('Template name :'.$templateName.' does not exists. Please add it in wd_et_design_cms.yaml');
        }

        return $this->config[$templateName]['template'];
    }

    /**
     * @param $name
     * @return mixed
     * @throws \Exception
     *
     * Get whole configuration of a template
     */
    public function getConfigurationFor($name)
    {
        if (!isset($this->config[$name])) {
            throw new \Exception('Configuration for :'.$name.' does not exists. Please add it in wd_et_design_cms.yaml');
        }

        return $this->config[$name];
    }
}
