<?php

declare(strict_types=1);

// Standalone, no kernel/EntityManager/database. Supply the integration autoloader.
// Baseline was captured on 9cf53fc30992f2552378ac6be238a161f316cc8e with ORM 2.20.13.
// --snapshot prints evidence; it never silently updates the checked-in baseline.
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Gedmo\Mapping\Driver\AttributeReader;

$autoload = $argv[1] ?? dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Usage: php tests/doctrine-attributes.php /path/to/vendor/autoload.php [--snapshot]\n");
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

/** Preserve every public metadata value, without filesystem/reflection state. */
function normalizeMapping(mixed $value): mixed
{
    if ($value instanceof ReflectionClass) {
        return ['reflectionClass' => $value->getName()];
    }
    if ($value instanceof ReflectionProperty) {
        return ['reflectionProperty' => $value->getDeclaringClass()->getName() . '::$' . $value->getName()];
    }
    if ($value instanceof BackedEnum) {
        return $value->value;
    }
    if ($value instanceof UnitEnum) {
        return $value->name;
    }
    if (is_object($value)) {
        return ['class' => $value::class, 'values' => normalizeMapping(get_object_vars($value))];
    }
    if (is_array($value)) {
        foreach ($value as $key => $item) {
            $value[$key] = normalizeMapping($item);
        }
        if (!array_is_list($value)) {
            ksort($value);
        }
    }
    return $value;
}

$classes = [
    \WebEtDesign\CmsBundle\Entity\CmsPage::class,
    \WebEtDesign\CmsBundle\Entity\CmsPageDeclination::class,
    \WebEtDesign\CmsBundle\Entity\CmsSharedBlock::class,
    CmsLegacySeoTraitMappingFixture::class,
];
$driver = new AttributeDriver([$root . '/src/Entity'], true);
$gedmo = new AttributeReader();
$snapshot = [];
$fields = $associations = $gedmoCount = 0;
foreach ($classes as $class) {
    $reflection = new ReflectionClass($class);
    if ($class !== CmsLegacySeoTraitMappingFixture::class &&
        !str_starts_with((string) $reflection->getFileName(), $root . '/src/')) {
        throw new RuntimeException('Integration vendor class loaded instead of candidate: ' . $class);
    }
    $metadata = new ClassMetadata($class);
    $metadata->initializeReflection(new RuntimeReflectionService());
    $driver->loadMetadataForClass($class, $metadata);
    $attributes = ['class' => normalizeMapping($gedmo->getClassAnnotations($reflection)), 'properties' => []];
    $gedmoCount += count($gedmo->getClassAnnotations($reflection));
    foreach ($reflection->getProperties() as $property) {
        $values = $gedmo->getPropertyAnnotations($property);
        if ($values !== []) {
            $attributes['properties'][$property->getName()] = normalizeMapping($values);
            $gedmoCount += count($values);
        }
    }
    $snapshot[$class] = normalizeMapping(['orm' => get_object_vars($metadata), 'gedmo' => $attributes]);
    $fields += count($metadata->fieldMappings);
    $associations += count($metadata->associationMappings);
}
if (in_array('--snapshot', $argv, true)) {
    print json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    exit(0);
}

$failures = [];
$baseline = json_decode(file_get_contents(__DIR__ . '/fixtures/doctrine-attributes-baseline.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ($classes as $class) {
    if (($baseline[$class] ?? null) !== $snapshot[$class]) {
        $failures[] = 'AttributeDriver/Gedmo metadata changed: ' . $class;
    }
}
if (array_keys($baseline) !== array_keys($snapshot)) {
    $failures[] = 'Baseline class set changed';
}
printf("Metadata comparison: %d classes, %d fields, %d associations, %d Gedmo declarations\n", count($classes), $fields, $associations, $gedmoCount);

// Real legacy reader: stale annotations must neither be parsed nor raise syntax errors.
// Only own declarations are checked; external SEO/Gedmo traits are outside this patch.
$reader = new AnnotationReader();
foreach ([...array_slice($classes, 0, 3), \WebEtDesign\CmsBundle\Entity\SeoAwareTrait::class] as $class) {
    $reflection = new ReflectionClass($class);
    $members = [[$reflection, 'getClassAnnotations', $class]];
    foreach ($reflection->getProperties() as $property) {
        if ($property->getDeclaringClass()->getName() === $class &&
            preg_match('/\\$' . preg_quote($property->getName(), '/') . '\\b/',
                file_get_contents((string) $reflection->getFileName())) === 1) {
            $members[] = [$property, 'getPropertyAnnotations', $class . '::$' . $property->getName()];
        }
    }
    foreach ($members as [$member, $method, $label]) {
        try {
            foreach ($reader->$method($member) as $annotation) {
                if (str_starts_with($annotation::class, 'Doctrine\\ORM\\Mapping\\') ||
                    str_starts_with($annotation::class, 'Gedmo\\Mapping\\Annotation\\')) {
                    $failures[] = 'Legacy runtime metadata still parsed: ' . $label . ' (' . $annotation::class . ')';
                }
            }
        } catch (Throwable $exception) {
            $failures[] = 'Legacy metadata reader failed: ' . $label . ': ' . $exception->getMessage();
        }
    }
}
$composer = json_decode(file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
if ($composer['require']['doctrine/orm'] !== '^2.20 || ^3.3') {
    $failures[] = 'ORM constraint must be ^2.20 || ^3.3';
}
if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
print "PASS: strict baseline unchanged; no legacy ORM/Gedmo runtime metadata; ORM constraint checked.\n";
