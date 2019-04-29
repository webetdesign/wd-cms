<?php
/**
 * Created by PhpStorm.
 * User: benjamin
 * Date: 16/04/2019
 * Time: 16:38
 */

namespace WebEtDesign\CmsBundle\Block\MailJet;

use Sonata\BlockBundle\Block\AbstractBlockService;

use Sonata\BlockBundle\Block\BlockContextInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;


class Base extends AbstractBlockService
{
    private $public_key;
    private $private_key;

    /**
     * @param string $name
     * @param EngineInterface $templating
     */
    public function __construct($name, EngineInterface $templating)
    {
        parent::__construct($name, $templating);
        $this->public_key = null;
        $this->private_key = null;

    }

    /**
     * @param BlockContextInterface $blockContext
     * @param Response|null $response
     * @return mixed
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $settings = $blockContext->getSettings();

        $template = $settings['template'];


        return $this->renderPrivateResponse($template, [
            'public_key' =>  $this->public_key,
            'private_key' =>  $this->private_key,
        ], $response);
    }

    public function getName()
    {
        return 'Admin MailJet';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'template' => "@WebEtDesignCmsBundle/Resources/views/block/analytics/base.html.twig",
            'public_key' => null,
            'private_key' => null,

        ]);

        $resolver->setAllowedTypes('template', ['string', 'boolean']);
        $resolver->setAllowedTypes('public_key', ['string', 'null']);
        $resolver->setAllowedTypes('private_key', ['string', 'null']);

    }

    public function updateSettings($settings){
        $this->public_key = $settings['public_key'];
        $this->private_key = $settings['private_key'];
    }
}
