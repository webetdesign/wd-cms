<?php

namespace WebEtDesign\CmsBundle\Services;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class PageProvider
 * @package WebEtDesign\CmsBundle\Services
 * Provide the configuration set in wd_cms.yaml
 */
class PageProvider
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     *
     * List of available templates for choice type
     */
    public function getTemplateList()
    {
        $list = [];

        foreach ($this->config as $key => $template) {
            $list[$template['label']] = $key;
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
            throw new \Exception('Template name :'.$templateName.' does not exists. Please add it in wd_cms.yaml');
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
            throw new \Exception('Configuration for :'.$name.' does not exists. Please add it in wd_cms.yaml');
        }

        return $this->config[$name];
    }
}
