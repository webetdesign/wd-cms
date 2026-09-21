<?php

namespace WebEtDesign\CmsBundle\CMS;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Enum\CmsVarsDelimiterEnum;
use WebEtDesign\CmsBundle\Registry\BlockRegistryInterface;
use WebEtDesign\CmsBundle\Registry\TemplateRegistryInterface;
use WebEtDesign\CmsBundle\Vars\CmsVarsBag;

abstract class AbstractConfiguration implements ConfigurationInterface
{
    private CmsVarsDelimiterEnum $cmsVarsDelimiter = CmsVarsDelimiterEnum::DOUBLE_UNDERSCORE;

    private BlockRegistryInterface $blockRegistry;

    private TemplateRegistryInterface $templateRegistry;

    private ?CmsVarsBag $varsBag = null;

    private ?CmsPage $currentPage = null;

    private bool $init = false;

    public function __construct(protected RequestStack $requestStack, protected EntityManagerInterface $em) { }


    public function getDisabledTemplate(): array
    {
        return [];
    }

    public function init(): void
    {
        if ($this->init) {
            return;
        }

        $this->init = true;
    }

    /**
     * @return CmsVarsBag|null
     */
    public function getVarsBag(): ?CmsVarsBag
    {
        if ($this->varsBag) {
            return $this->varsBag;
        }

        if (!$this->getCurrentPage()) {
            return null;
        }

        $template = $this->templateRegistry->get($this->getCurrentPage()->getTemplate());

        $this->varsBag = $template->getVarsBag();

        return $this->varsBag;
    }

    public function autoPopulateVars()
    {
        $bag = $this->getVarsBag();
        if (!$bag) {
            return;
        }
        $request = $this->requestStack->getCurrentRequest();

        foreach ($bag->getDefinitions() as $definition) {
            if ($definition->getRouteAttributeKey() && $request->attributes->has($definition->getRouteAttributeKey())) {
                $class  = $definition->getClass();
                $method = $definition->getFindOneBy() ?: 'findOneBy' . ucfirst($definition->getRouteAttributeKey());
                $object = $this->em->getRepository($class)->$method($request->attributes->get($definition->getRouteAttributeKey()));


                if ($object instanceof $class) {
                    $this->getVarsBag()->populate($definition->getCode(), $object);
                }
            }
        }
    }

    /**
     * @return BlockRegistryInterface
     */
    public function getBlockRegistry(): BlockRegistryInterface
    {
        return $this->blockRegistry;
    }

    /**
     * @param BlockRegistryInterface $blockRegistry
     * @return AbstractConfiguration
     */
    public function setBlockRegistry(BlockRegistryInterface $blockRegistry): AbstractConfiguration
    {
        $this->blockRegistry = $blockRegistry;
        return $this;
    }

    /**
     * @return TemplateRegistryInterface
     */
    public function getTemplateRegistry(): TemplateRegistryInterface
    {
        return $this->templateRegistry;
    }

    /**
     * @param TemplateRegistryInterface $templateRegistry
     * @return AbstractConfiguration
     */
    public function setTemplateRegistry(TemplateRegistryInterface $templateRegistry): AbstractConfiguration
    {
        $this->templateRegistry = $templateRegistry;
        $this->templateRegistry->setConfiguration($this);
        return $this;
    }

    /**
     * @return CmsVarsDelimiterEnum
     */
    public function getCmsVarsDelimiter(): CmsVarsDelimiterEnum
    {
        return $this->cmsVarsDelimiter;
    }

    /**
     * @param CmsVarsDelimiterEnum $cmsVarsDelimiter
     * @return AbstractConfiguration
     */
    public function setCmsVarsDelimiter(CmsVarsDelimiterEnum $cmsVarsDelimiter): AbstractConfiguration
    {
        $this->cmsVarsDelimiter = $cmsVarsDelimiter;
        return $this;
    }

    /**
     * @return CmsPage|null
     */
    public function getCurrentPage(): ?CmsPage
    {
        return $this->currentPage;
    }

    /**
     * @param CmsPage|null $currentPage
     * @return AbstractConfiguration
     */
    public function setCurrentPage(?CmsPage $currentPage): AbstractConfiguration
    {
        $this->currentPage = $currentPage;
        return $this;
    }
}
