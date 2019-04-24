<?php
/**
 * Created by PhpStorm.
 * User: benjamin
 * Date: 16/04/2019
 * Time: 16:38
 */

namespace WebEtDesign\CmsBundle\Block\Analytics;

use Sonata\BlockBundle\Block\AbstractBlockService;

use Sonata\BlockBundle\Block\BlockContextInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;


class Source extends AbstractBlockService
{
    /**
     * @param string $name
     * @param EngineInterface $templating
     */
    public function __construct($name, EngineInterface $templating)
    {
        parent::__construct($name, $templating);

    }

    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {

        return $this->renderPrivateResponse("@WebEtDesignCmsBundle/Resources/views/block/analytics/source.html.twig", [], $response);
    }

    public function getName()
    {
        return 'Admin Analytics';
    }

    public function configureSettings(OptionsResolver $resolver)
    {

    }
}
