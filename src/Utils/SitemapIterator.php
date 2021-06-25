<?php
/**
 * Created by PhpStorm.
 * User: benjamin
 * Date: 06/09/2019
 * Time: 09:08
 */

namespace WebEtDesign\CmsBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\Exporter\Source\SourceIteratorInterface;
use Symfony\Component\Routing\RouterInterface;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use function Doctrine\ORM\QueryBuilder;

class SitemapIterator implements SourceIteratorInterface
{

    protected $current;

    /**
     * @var \ArrayIterator
     */
    protected $sources;

    /**
     * @var string
     */
    protected $groupName = 'cms';

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em, RouterInterface $router)
    {
        $this->sources = new \ArrayIterator();
        $this->router  = $router;
        $this->em      = $em;
    }

    public function configure($host = null): void
    {
        try {
            $con = $this->em->getConnection();
            $con->connect();

            $sources = $this->em->getRepository(CmsRoute::class)->findAll();

            foreach ($sources as $route) {
                /** @var $route CmsRoute */
                if ($route->getPage() === null) {
                    continue;
                }
                if ($route->getPage()->getActive() === false) {
                    continue;
                }
                if ($route->getPage()->getSite() === null) {
                    continue;
                }
                if ($host !== null && strpos($route->getPage()->getSite()->getHost(), $host) === false) {
                    continue;
                }
                if (strpos($route->getPath(), '{', true) === false) {
                    $this->addSource($route);
                }
            }
        } catch (\Exception $exception) {

        }
    }

    public function current()
    {
        /** @var CmsRoute $current */
        $current = $this->sources->current();
        $this->current = [
            'url'        => $this->router->generate($current->getName(), [], RouterInterface::ABSOLUTE_URL),
            'lastmod'    => 'now',
            'changefreq' => $current->getPath() == '/' ? 'monthly' : 'yearly',
            'priority'   => $current->getPath() == '/' ? 1 : 0.5,
        ];

        return $this->current;
    }

    public function next()
    {
        $this->sources->next();
    }

    public function key()
    {
        return $this->sources->key();
    }

    public function valid()
    {
        return $this->sources->current() !== null;
    }

    public function rewind()
    {
        $this->sources->rewind();
    }

    public function getGroupName()
    {
        $this->groupName;
    }

    public function addSource(CmsRoute $route)
    {
        $this->sources->append($route);
    }
}
