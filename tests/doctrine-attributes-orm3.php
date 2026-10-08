<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Gedmo\Mapping\Driver\AttributeReader;

$autoload = $argv[1] ?? dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Usage: php tests/doctrine-attributes-orm3.php /path/to/orm3/vendor/autoload.php\n");
    exit(2);
}
require $autoload;
$root = dirname(__DIR__);
$prefix = 'WebEtDesign\\CmsBundle\\';
spl_autoload_register(static function (string $class) use ($root, $prefix): void {
    if (str_starts_with($class, $prefix)) {
        $file = $root . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
}, true, true);

#[\Doctrine\ORM\Mapping\Entity]
class CmsLegacySeoTraitMappingFixture
{
    use \WebEtDesign\CmsBundle\Entity\SeoAwareTrait;
}

function invariant(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException($label);
    }
}

/** Normalize representation only; never change the recorded ORM 2 baseline. */
function mappingValue(mixed $value): mixed
{
    if ($value instanceof UnitEnum && $value::class === 'SortDirection') {
        return match ($value->name) {
            'Ascending' => 'ASC',
            'Descending' => 'DESC',
        };
    }
    if ($value instanceof BackedEnum) {
        return $value->value;
    }
    if ($value instanceof UnitEnum) {
        return $value->name;
    }
    if (is_object($value)) {
        return mappingValue(get_object_vars($value));
    }
    if (is_array($value)) {
        foreach ($value as $key => $item) {
            $value[$key] = mappingValue($item);
        }
        if (!array_is_list($value)) {
            ksort($value);
        }
    }
    return $value;
}

/** Compare every recorded mapping value; allow new ORM 3 internal defaults. */
function compareRecorded(mixed $expected, mixed $actual, string $label): void
{
    if (!is_array($expected)) {
        invariant($expected === $actual, $label . ': expected ' . json_encode($expected) . ', got ' . json_encode($actual));
        return;
    }
    invariant(is_array($actual), $label . ': array expected');
    if (array_is_list($expected)) {
        invariant(count($expected) === count($actual), $label . ': list size changed');
    }
    foreach ($expected as $key => $value) {
        invariant(array_key_exists($key, $actual), $label . ': missing key ' . $key);
        compareRecorded($value, $actual[$key], $label . '.' . $key);
    }
}

function gedmoValue(mixed $value): mixed
{
    if (is_object($value)) {
        return ['class' => $value::class, 'values' => gedmoValue(get_object_vars($value))];
    }
    if (is_array($value)) {
        foreach ($value as $key => $item) {
            $value[$key] = gedmoValue($item);
        }
        if (!array_is_list($value)) {
            ksort($value);
        }
    }
    return $value;
}

invariant(class_exists(AssociationMapping::class), 'This test requires real ORM 3 mapping objects');
$baseline = json_decode(file_get_contents(__DIR__ . '/fixtures/doctrine-attributes-baseline.json'), true, 512, JSON_THROW_ON_ERROR);
$driver = new AttributeDriver([$root . '/src/Entity'], true);
$reader = new AttributeReader();
$fields = $associations = $gedmo = 0;
foreach ($baseline as $class => $recorded) {
    $reflection = new ReflectionClass($class);
    if ($class !== CmsLegacySeoTraitMappingFixture::class) {
        invariant(str_starts_with($reflection->getFileName(), $root . '/src/'), 'Vendor entity loaded: ' . $class);
    }
    $metadata = new ClassMetadata($class);
    $metadata->initializeReflection(new RuntimeReflectionService());
    $driver->loadMetadataForClass($class, $metadata);
    $expected = $recorded['orm'];
    foreach (['name', 'table', 'identifier', 'generatorType', 'inheritanceType', 'isMappedSuperclass', 'isEmbeddedClass', 'isReadOnly', 'isVersioned', 'versionField', 'customRepositoryClassName', 'discriminatorMap', 'lifecycleCallbacks'] as $key) {
        compareRecorded($expected[$key], mappingValue($metadata->$key), $class . '.' . $key);
    }
    foreach (['fieldMappings', 'associationMappings'] as $key) {
        $expectedNames = array_keys($expected[$key]);
        $actualNames = array_keys($metadata->$key);
        sort($expectedNames);
        sort($actualNames);
        invariant($expectedNames === $actualNames, $class . '.' . $key . ': property set changed');
    }
    foreach ($metadata->fieldMappings as $name => $mapping) {
        ++$fields;
        compareRecorded($expected['fieldMappings'][$name], mappingValue($mapping), $class . '.' . $name);
    }
    foreach ($metadata->associationMappings as $name => $mapping) {
        ++$associations;
        invariant($mapping instanceof AssociationMapping, $class . '.' . $name . ': real ORM 3 AssociationMapping expected');
        $actual = mappingValue($mapping);
        $actual['type'] = $mapping->type();
        $actual['isOwningSide'] = $mapping->isOwningSide();
        foreach (['Persist', 'Remove', 'Detach', 'Refresh'] as $operation) {
            $method = 'isCascade' . $operation;
            $actual[$method] = $mapping->$method();
        }
        // Merge is removed in ORM 3: ensure the baseline never requested it.
        invariant($expected['associationMappings'][$name]['isCascadeMerge'] === false, 'Baseline requires removed merge semantics');
        $actual['isCascadeMerge'] = in_array('merge', $mapping->cascade, true);
        // Owning/inverse subclasses omit the irrelevant counterpart property.
        $actual['mappedBy'] ??= null;
        $actual['inversedBy'] ??= null;
        $recordedAssociation = $expected['associationMappings'][$name];
        if ($mapping->isManyToManyOwningSide()) {
            // ORM 3 forces join-table PK columns nullable=false at mapping time.
            // ORM 2 SchemaTool::gatherRelations sets the same PK, and DBAL
            // Table::setPrimaryKey forces notnull=true. Compare that semantic
            // invariant, not ORM 2's misleading intermediate nullable=true.
            foreach (['joinColumns', 'inverseJoinColumns'] as $side) {
                foreach ($recordedAssociation['joinTable'][$side] as $index => &$column) {
                    invariant($actual['joinTable'][$side][$index]['nullable'] === false, 'ORM 3 join-table PK must be non-nullable');
                    $column['nullable'] = false;
                }
                unset($column);
            }
        }
        compareRecorded($recordedAssociation, $actual, $class . '.' . $name);
    }
    $attributes = ['class' => gedmoValue($reader->getClassAnnotations($reflection)), 'properties' => []];
    $gedmo += count($reader->getClassAnnotations($reflection));
    foreach ($reflection->getProperties() as $property) {
        $values = $reader->getPropertyAnnotations($property);
        if ($values !== []) {
            $attributes['properties'][$property->getName()] = gedmoValue($values);
            $gedmo += count($values);
        }
    }
    invariant(mappingValue($recorded['gedmo']) === mappingValue($attributes), 'Gedmo declarations changed: ' . $class);
}
printf("PASS: ORM 3 semantic invariants: %d classes, %d fields, %d associations, %d Gedmo declarations; no DB\n", count($baseline), $fields, $associations, $gedmo);
print class_exists(\Doctrine\Common\Annotations\AnnotationReader::class)
    ? "Legacy AnnotationReader available; legacy cleanup is covered separately by the ORM 2 test.\n"
    : "No legacy AnnotationReader available; real AttributeDriver/Gedmo invariants executed without doctrine/annotations.\n";
