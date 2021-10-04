<?php


namespace WebEtDesign\CmsBundle\Services;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;

class WDDeclinationService
{

    private RequestStack          $requestStack;
    private ParameterBagInterface $parameterBag;

    public function __construct(RequestStack $requestStack, ParameterBagInterface $parameterBag)
    {
        $this->requestStack = $requestStack;
        $this->parameterBag = $parameterBag;
    }


    public function getDeclination(CmsPage $page): ?CmsPageDeclination
    {
        $request    = $this->requestStack->getCurrentRequest();
        $path       = $request->getRequestUri();
        $cms_config = $this->parameterBag->get('wd_cms.cms');

        $path             = preg_replace('(\?.*)', '', $path);
        $withoutExtension = $cms_config['page_extension'] ? preg_replace('/\.([a-z]+)$/', '',
            $path) : false;

        $declinations = $page->getDeclinations()->toArray();

        uasort($declinations, function (CmsPageDeclination $a, CmsPageDeclination $b) {
            return strlen($a->getPath()) < strlen($b->getPath());
        });

        /** @var CmsPageDeclination $declination */
        foreach ($declinations as $declination) {
            $dPath = $declination->getPath();

            if ($cms_config['multilingual'] && !empty($page->getSite()->getLocale())) {
                $dPath = '/' . $page->getSite()->getLocale() . $dPath;
            }

            if (
                preg_match('/^' . preg_quote($dPath, '/') . '/', $path) ||
                preg_match('/^' . preg_quote($dPath, '/') . '/', $withoutExtension)
            ) {
                return $declination;
                break;
            }
        }

        return null;
    }

}
