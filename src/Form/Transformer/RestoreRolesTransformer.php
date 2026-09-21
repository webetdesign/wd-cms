<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebEtDesign\CmsBundle\Form\Transformer;


use Symfony\Component\Form\DataTransformerInterface;
use WebEtDesign\CmsBundle\Security\EditableRolesBuilder;

class RestoreRolesTransformer implements DataTransformerInterface
{
    /**
     * @var array|null
     */
    protected ?array $originalRoles = null;

    /**
     * @var EditableRolesBuilder|null
     */
    protected ?EditableRolesBuilder $rolesBuilder = null;

    public function __construct(EditableRolesBuilder $rolesBuilder)
    {
        $this->rolesBuilder = $rolesBuilder;
    }

    public function setOriginalRoles(?array $originalRoles = null): void
    {
        $this->originalRoles = $originalRoles ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value): mixed
    {
        if (null === $value) {
            return $value;
        }

        if (null === $this->originalRoles) {
            throw new \RuntimeException('Invalid state, originalRoles array is not set');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($selectedRoles): array
    {
        if (null === $this->originalRoles) {
            throw new \RuntimeException('Invalid state, originalRoles array is not set');
        }

        $availableRoles = $this->rolesBuilder->getRoles();

        $hiddenRoles = array_diff($this->originalRoles, array_keys($availableRoles));

        return array_merge($selectedRoles, $hiddenRoles);
    }
}
