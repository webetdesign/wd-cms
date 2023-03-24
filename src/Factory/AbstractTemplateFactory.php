<?php

namespace WebEtDesign\CmsBundle\Factory;

use Symfony\Component\DependencyInjection\ServiceLocator;
use WebEtDesign\CmsBundle\CmsTemplate\TemplateInterface;
use WebEtDesign\CmsBundle\Services\CmsConfigurationInterface;
use WebEtDesign\UserBundle\CMS\Pages\LoginPage;
use WebEtDesign\UserBundle\CMS\Pages\ResetPasswordPage;

abstract class AbstractTemplateFactory implements TemplateFactoryInterface
{
    protected ServiceLocator             $serviceLocator;
    protected array                      $configs;
    protected ?CmsConfigurationInterface $configuration = null;

    public function __construct(ServiceLocator $templates, array $configs)
    {
        $this->serviceLocator = $templates;
        $this->configs        = $configs;
    }

    public function get($code): TemplateInterface
    {
        return $this->mount($code);
    }

    public function getTemplateList($collection = null): array
    {
        $tpls = [];
        foreach ($this->serviceLocator->getProvidedServices() as $key => $id) {
            $tpl = $this->getServices($key);
            if ($tpl && ($tpl->getCollections() === null || $collection === null || in_array($collection,
                        $tpl->getCollections()))) {
                $tpls[$key] = $tpl;
            }
        }

        return $tpls;
    }

    public function getTemplateChoices($collection = null): array
    {
        $tpls = [];
        foreach ($this->serviceLocator->getProvidedServices() as $key => $id) {
            $tpl = $this->getServices($key);
            if (
                $tpl &&
                !$this->isDisabled($id, $key) &&
                (
                    $tpl->getCollections() === null ||
                    $collection === null ||
                    in_array($collection, $tpl->getCollections())
                )
            ) {

                if (!empty($tpl->getCollections())) {
                    $collString = implode(', ', $tpl->getCollections()) . ' — ';
                }

                $tpls[($collString ?? null) . $tpl->getLabel()] = $key;
            }
        }

        ksort($tpls);

        return $tpls;
    }

    /**
     * @param CmsConfigurationInterface|null $configuration
     * @return self
     */
    public function setConfiguration(?CmsConfigurationInterface $configuration
    ): self {
        $this->configuration = $configuration;
        return $this;
    }

    private function isDisabled($id, $code): bool
    {
        if ($this->configuration === null) {
            return false;
        }

        return in_array($id, $this->configuration->getDisabledTemplate()) ||
            in_array($code, $this->configuration->getDisabledTemplate());
    }
}
