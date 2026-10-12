<?php

declare(strict_types=1);

/**
 * Regression check: every callback declared by getSubscribedEvents() of the CMS form
 * listeners must exist (a missing one only fails at dispatch time, on the admin form).
 *
 * Usage: php tests/subscribed-events-callbacks.php [path/to/vendor/autoload.php]
 */

require $argv[1] ?? __DIR__ . '/../vendor/autoload.php';

$classes = [
    WebEtDesign\CmsBundle\EventListener\CmsBlockResizeFormListener::class,
    WebEtDesign\CmsBundle\EventListener\CmsDynamicBlockResizeFormListener::class,
    WebEtDesign\CmsBundle\EventListener\JsonFormListener::class,
];

$failures = 0;
foreach ($classes as $class) {
    foreach ($class::getSubscribedEvents() as $event => $listener) {
        $method = is_array($listener) ? (is_array($listener[0]) ? $listener[0][0] : $listener[0]) : $listener;
        $ok = method_exists($class, $method);
        printf("%s %s::%s (%s)\n", $ok ? 'PASS' : 'FAIL', substr(strrchr($class, '\\'), 1), $method, $event);
        $failures += $ok ? 0 : 1;
    }
}

exit($failures > 0 ? 1 : 0);
