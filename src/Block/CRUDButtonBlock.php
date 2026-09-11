<?php
namespace WebEtDesign\CmsBundle\Block;

use Exception;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

/**
 * Class CRUDButtonBlock
 * @package WebEtDesign\CmsBundle\Block
 */
class CRUDButtonBlock extends AbstractBlockService
{
    /**
     * @var Pool
     */
    private $pool;

    /**
     * CRUDButtonBlock constructor.
     * @param Pool $pool
     * @param Environment $environment
     */
    public function __construct(Pool $pool, Environment $environment)
    {
        parent::__construct($environment);

        $this->pool = $pool;
    }

    /**
     * @param BlockContextInterface $blockContext
     * @param Response|null $response
     * @return Response|void
     * @throws Exception
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null): Response
    {

        $admin = $this->pool->getAdminByAdminCode($blockContext->getSetting('code'));

        if (!$admin){
            throw new Exception("Admin can't be found");
        }

        return $this->renderPrivateResponse("@WebEtDesignCms/block/crud_button.html.twig", [
            'admin' => $admin,
            'settings' => $blockContext->getSettings(),
        ], $response);
    }

    public function getName()
    {
        return 'Admin CRUD Button';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureSettings(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'icon' => 'fa-line-chart',
            'text' => 'Statistics',
            'color' => 'bg-aqua',
            'button' => 'Consulter',
            'code' => false,
        ]);

        $resolver->setAllowedTypes('icon', ['string', 'null']);
        $resolver->setAllowedTypes('text', ['string', 'null']);
        $resolver->setAllowedTypes('color', ['string', 'null']);
        $resolver->setAllowedTypes('button', ['string', 'null']);
        $resolver->setAllowedTypes('code', ['string', 'boolean', 'null']);
    }
}
