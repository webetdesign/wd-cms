<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WebEtDesign\CmsBundle\Controller\BaseCmsController;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsRoute;

$autoload = $argv[1] ?? dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Usage: php tests/controller-declination.php /path/to/vendor/autoload.php [case]\n");
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

$cases = [
    'exact' => ['/news/one', false, 'one'],
    'query' => ['/news/one?next=/news/main.html&slug=main', false, 'one'],
    'extension-enabled' => ['/news/one.html', '.html', 'one'],
    'extension-query' => ['/news/one.html?slug=main', '.html', 'one'],
    'extension-disabled' => ['/news/one.html', false, 'one.html'],
    'extension-disabled-no-match' => ['/news/main.html', false, null],
    'extension-uppercase' => ['/news/one.HTML', '.html', null],
    'no-match' => ['/news/unknown?slug=one', '.html', null],
];
$failures = $executed = 0;
foreach ($cases as $name => [$uri, $extension, $expected]) {
    if (isset($argv[2]) && $argv[2] !== $name) {
        continue;
    }
    ++$executed;
    try {
        $controller = new BaseCmsController();
        if ((new ReflectionClass($controller))->getFileName() !== $root . '/src/Controller/BaseCmsController.php') {
            throw new RuntimeException('Candidate controller not loaded');
        }
        $services = BaseCmsController::getSubscribedServices();
        if (($services['request_stack'] ?? null) !== '?' . RequestStack::class) {
            throw new RuntimeException('Inherited request_stack subscription missing');
        }
        $stack = new RequestStack();
        $stack->push(Request::create('/news/main'));
        $stack->push(Request::create($uri));
        $container = new ContainerBuilder();
        $container->set('request_stack', $stack);
        $controller->setContainer($container);
        $controller->setCmsConfig(['declination' => true, 'page_extension' => $extension]);
        $page = new CmsPage();
        $page->setRoute((new CmsRoute())->setPath('/news/{slug}'));
        $declinations = [];
        foreach (['main', 'one', 'one.html'] as $slug) {
            $declination = (new CmsPageDeclination())->setParams(json_encode(['slug' => $slug], JSON_THROW_ON_ERROR));
            $page->addDeclination($declination);
            $declinations[$slug] = $declination;
        }
        if ($controller->getDeclination($page) !== ($expected === null ? null : $declinations[$expected])) {
            throw new RuntimeException('Wrong declination identity: current request/path matching changed');
        }
        print "PASS: $name\n";
    } catch (Throwable $exception) {
        ++$failures;
        fwrite(STDERR, "FAIL: $name: " . $exception::class . ': ' . $exception->getMessage() . "\n");
    }
}
if ($executed === 0) {
    fwrite(STDERR, "Unknown case\n");
    exit(2);
}
printf("Declination regression: %d cases, %d failures; real container/RequestStack, no database\n", $executed, $failures);
exit($failures === 0 ? 0 : 1);
