<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 12/02/2019
 * Time: 15:15
 */

namespace WebEtDesign\CmsBundle\Entity;


interface CmsRouteInterface
{
    public function getId(): ?int;

    public function getName(): ?string;

    public function getMethods(): ?array;

    public function getPath(): ?string;

    public function getPage(): ?CmsPage;

    public function getController(): ?string;

    public function getDefaults(): ?string;

    public function getRequirements(): ?string;
}
