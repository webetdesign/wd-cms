<?php


namespace WebEtDesign\CmsBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;

/**
 * Class SitemapController
 * @package WebEtDesign\CmsBundle\Controller
 *
 */
class SitemapController extends AbstractController
{
    /**
     * @var CmsSiteRepository
     */
    private $cmsSiteRepository;

    public function __construct(CmsSiteRepository $cmsSiteRepository) {
        $this->cmsSiteRepository = $cmsSiteRepository;
    }

    /**
     * @param Request $request
     */
    public function __invoke(Request $request)
    {
        $site = $this->cmsSiteRepository->findOneByHost($request->getHost());

        if (!$site) {
            return new NotFoundHttpException();
        }

        $uri = 'sitemaps/'.$site->getSlug().'/sitemap.xml';

        return $this->redirect($uri);
    }

}
