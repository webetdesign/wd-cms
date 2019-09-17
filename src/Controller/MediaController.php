<?php

namespace WebEtDesign\CmsBundle\Controller;

use App\Entity\Media;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MediaController extends Controller
{
    public function getMediaAction(Request $request, $id = 0, $format = 'big'){
        /** @var Media $media */
        $media = $this->getDoctrine()->getManager()->getRepository(Media::class)->findOneBy([
            "id" => $id
        ]);

        if ($media){
            $provider = $this->container->get($media->getProviderName());
            $format = $provider->getFormatName($media, $format);


            return new JsonResponse([
                "id" => $media->getId(),
                "name" => $media->getName(),
                "link" => $provider->generatePublicUrl($media, $format)
            ]);
        }else{
            return new JsonResponse(["media not found"]);
        }
    }
}
