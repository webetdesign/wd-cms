<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

#[ORM\Entity]
#[ORM\Table(name: 'cms__log_entry', options: ['row_format' => 'DYNAMIC'])]
#[ORM\Index(columns: ['object_class'], name: 'log_class_lookup_idx')]
#[ORM\Index(columns: ['logged_at'], name: 'log_date_lookup_idx')]
#[ORM\Index(columns: ['username'], name: 'log_user_lookup_idx')]
#[ORM\Index(columns: ['object_id', 'object_class', 'version'], name: 'log_version_lookup_idx')]
class CmsLogEntry extends AbstractLogEntry
{
    /*
     * All columns but « data » are mapped through inherited superclass.
     * « data » is redeclared here: the superclass maps it as « array », a DBAL
     * type removed in DBAL 4, so the entity cannot be mapped under ORM 3
     * without it. AttributeOverride cannot do this — ORM refuses to change a
     * column TYPE that way, only its name, length or nullability.
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected $data;
}
