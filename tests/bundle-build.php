<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\ErrorHandler\DebugClassLoader;
use WebEtDesign\CmsBundle\DependencyInjection\Compiler\BlockPass;
use WebEtDesign\CmsBundle\DependencyInjection\Compiler\ConfigurationPass;
use WebEtDesign\CmsBundle\DependencyInjection\Compiler\TemplatePass;
use WebEtDesign\CmsBundle\WebEtDesignCmsBundle;

$autoload = $argv[1] ?? dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Usage: php tests/bundle-build.php /path/to/vendor/autoload.php\n");
    exit(2);
}
require $autoload;
$root = dirname(__DIR__);
$prefix = 'WebEtDesign\\CmsBundle\\';
$loader = static function (string $class) use ($root, $prefix): void {
    if (str_starts_with($class, $prefix)) {
        $file = $root . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
};
spl_autoload_register([new DebugClassLoader($loader), 'loadClass'], true, true);
$deprecations = [];
set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
    if ($type === E_USER_DEPRECATED && str_contains($message, '::build()') && str_contains($message, WebEtDesignCmsBundle::class)) {
        $deprecations[] = $message;
        return true;
    }
    return false;
});
try {
    $bundle = new WebEtDesignCmsBundle();
    if ((new ReflectionClass($bundle))->getFileName() !== $root . '/src/WebEtDesignCmsBundle.php') {
        throw new RuntimeException('Candidate bundle not loaded');
    }
    $container = new ContainerBuilder();
    $before = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
    $bundle->build($container);
    $after = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
    $added = array_values(array_filter($after, static fn ($pass): bool => !in_array($pass, $before, true)));
    if (array_map(static fn ($pass): string => $pass::class, $added) !== [BlockPass::class, TemplatePass::class, ConfigurationPass::class]) {
        throw new RuntimeException('Compiler pass registration/order changed');
    }
    if ($deprecations !== []) {
        throw new RuntimeException(implode("\n", $deprecations));
    }
    print "PASS: build has no Symfony return-type deprecation and registers all three compiler passes in order\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: build: ' . $exception::class . ': ' . $exception->getMessage() . "\n");
    exit(1);
} finally {
    restore_error_handler();
}
