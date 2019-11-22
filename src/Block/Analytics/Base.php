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
use WebEtDesign\CmsBundle\Services\Analytics;

class Base extends AbstractBlockService
{
    /**
     * @var Analytics
     */
    private $analyticsService;

    /**
     * @param string $name
     * @param EngineInterface $templating
     * @param Analytics $analyticsService
     */
    public function __construct($name, EngineInterface $templating, Analytics $analyticsService)
    {
        parent::__construct($name, $templating);

        $this->analyticsService = $analyticsService;
    }

    /**
     * @param BlockContextInterface $blockContext
     * @param Response|null $response
     * @return mixed
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $settings = $blockContext->getSettings();

        $blocks = [];

        $this->analyticsService->maxPage = sizeof($settings['colors']);

        foreach ($settings["analytics"] as $block) {
            $block_name = array_key_first($block);
            $start = sizeof($block[$block_name]) == 2 ? $block[$block_name][1] : null;
            $method = "get" . ucfirst($block_name);
            $row = [];
            $row["template"] = "@WebEtDesignCms/block/analytics/" . $block_name . ".html.twig";
            $start ? $row["data"] = $this->analyticsService->$method($start) : $row["data"] = $this->analyticsService->$method();
            $row["name"] = $block_name;
            $row["size"] = $block[$block_name] ? $block[$block_name][0] : null;
            $blocks[] = $row;
        }

        return $this->renderPrivateResponse("@WebEtDesignCms/block/analytics/base.html.twig", [
            'map_key' => $settings['map_key'] ,
            'map_color' => $settings['map_color'],
            'users_color' => $settings['users_color'],
            'week_colors' => json_encode($settings['week_colors']),
            'year_colors' => json_encode($settings['year_colors']),
            'colors' => json_encode($settings['colors']),
            'blocks' => $blocks
        ], $response);
    }

        public function getName()
    {
        return 'Admin Analytics';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'map_key' => null,
            'map_color' => "#0077ae",
            'users_color' => 'rgb(179, 000, 000)',
            'week_colors' => ['rgb(255, 077, 077)', 'rgb(230, 000, 000)'],
            'year_colors' => ['rgb(255, 077, 077)', 'rgb(230, 000, 000)'],
            'colors' => ['rgb(255, 102, 102)','rgb(255, 051, 051)','rgb(230, 000, 000)','rgb(179, 000, 000)','rgb(128, 000, 000)'],
            "analytics" => []

        ]);

        $resolver->setAllowedTypes('map_key', ['string', 'null']);
        $resolver->setAllowedTypes('week_colors', ['array', 'null']);
        $resolver->setAllowedTypes('year_colors', ['array', 'null']);
        $resolver->setAllowedTypes('users_color', ['string', 'null']);
        $resolver->setAllowedTypes('map_color', ['string', 'null']);
        $resolver->setAllowedTypes('colors', ['array', 'null']);
        $resolver->setAllowedTypes('analytics', ['array', 'null']);


    }
}
