<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Entity;

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
     * All required columns are mapped through inherited superclass
     */
}
