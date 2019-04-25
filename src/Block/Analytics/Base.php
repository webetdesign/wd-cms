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


class Base extends AbstractBlockService
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
        $settings = $blockContext->getSettings();

        $template = $settings['template'];
        $client_key = $settings['client_key'];
        $users_color = $settings['users_color'];
        $colors = $settings['colors'];


        return $this->renderPrivateResponse($template, [
            'client_key' => $client_key ,
            'users_color' => $users_color,
            'colors' => json_encode($colors)
        ], $response);
    }

    public function getName()
    {
        return 'Admin Analytics';
    }

    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'template' => "@WebEtDesignCmsBundle/Resources/views/block/analytics/base.html.twig",
            'client_key' => null,
            'users_color' => 'rgb(255, 026, 026)',
            'colors' => ['#4D5360','#949FB1','#D4CCC5','#E2EAE9','#F7464A']

        ]);

        $resolver->setAllowedTypes('template', ['string', 'boolean']);
        $resolver->setAllowedTypes('client_key', ['string', 'null']);
        $resolver->setAllowedTypes('users_color', ['string', 'null']);
        $resolver->setAllowedTypes('colors', ['array', 'null']);

    }
}
