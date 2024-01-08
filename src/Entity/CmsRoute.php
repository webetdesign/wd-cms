<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 12/02/2019
 * Time: 17:49
 */

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use WebEtDesign\CmsBundle\Repository\CmsRouteRepository;

/**
 * Class CmsRoute
 * @package WebEtDesign\CmsBundle\Entity
 */
#[ORM\Entity(repositoryClass: CmsRouteRepository::class)]
#[ORM\Table(name: 'cms__route')]
class CmsRoute extends AbstractCmsRoute
{

}
