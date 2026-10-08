<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Event\LifecycleEventArgs as PersistenceEventArgs;
use Sonata\Doctrine\Mapper\DoctrineCollector;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use WebEtDesign\CmsBundle\DependencyInjection\WebEtDesignCmsExtension;
use WebEtDesign\CmsBundle\Entity\AbstractCmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsRoute;

$autoload = $argv[1] ?? dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Usage: php tests/doctrine-lifecycle.php /path/to/vendor/autoload.php [test-name]\n");
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

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// No parent constructor: no Connection, UnitOfWork, SQL or actual flush.
// Broad parameters + void return support both ORM 2.20 and ORM 3.3 signatures.
final class RecordingEntityManager extends EntityManager
{
    public array $persisted = [];
    public int $flushes = 0;

    public function __construct() {}

    public function persist($object): void
    {
        $this->persisted[] = $object;
    }

    public function flush($object = null): void
    {
        ++$this->flushes;
    }
}

class CmsRouteOverrideFixture extends CmsRoute {}

$tests = [];
$tests['extension'] = static function () use ($root): void {
    $reflection = new ReflectionClass(WebEtDesignCmsExtension::class);
    check($reflection->getFileName() === $root . '/src/DependencyInjection/WebEtDesignCmsExtension.php', 'Candidate extension not loaded');
    $collector = DoctrineCollector::getInstance();
    foreach ([CmsRoute::class, CmsRouteOverrideFixture::class] as $routeClass) {
        $collector->clear();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', []);
        (new WebEtDesignCmsExtension())->load([[
            'class' => ['user' => stdClass::class],
            'admin' => ['configuration' => ['entity' => ['route' => $routeClass]]],
        ]], $container);
        check(!$container->isCompiled(), 'Test must run before DI compilation');
        check($container->getParameter('wd_cms.admin.content.user') === stdClass::class, 'User class configuration lost');
        check($collector->getInheritanceTypes()[AbstractCmsRoute::class] === ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE, 'Single-table inheritance lost');
        $expected = ['base' => CmsRoute::class];
        if ($routeClass !== CmsRoute::class) {
            $expected['override'] = $routeClass;
        }
        check($collector->getDiscriminators()[AbstractCmsRoute::class] === $expected, 'Route discriminator changed');
        check($collector->getDiscriminatorColumns()[AbstractCmsRoute::class] === ['name' => 'discr', 'type' => 'string'], 'Discriminator column changed');
    }
    $collector->clear();
    // ORM 2 still exposes this removed symbol: a narrowly scoped compatibility
    // guard complements real extension execution rather than mocking Doctrine.
    foreach (token_get_all(file_get_contents($reflection->getFileName())) as $token) {
        if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
            check(!str_ends_with($token[1], 'ClassMetadataInfo'), 'ORM 3 removed ClassMetadataInfo: extension still references it');
        }
    }
};

$tests['shared-block'] = static function (): void {
    $container = new ContainerBuilder();
    $container->set('indexable', new class {
        public function getIndexableData(\WebEtDesign\CmsBundle\Entity\CmsContent $content): string
        {
            return 'custom:' . $content->getValue();
        }
    });
    $container->set('not-indexable', new stdClass());
    $listener = new \WebEtDesign\CmsBundle\EventListener\SharedBlockListener([
        'CUSTOM' => ['service' => 'indexable'],
        'NO_INDEX' => ['service' => 'not-indexable'],
    ], $container);
    $em = new RecordingEntityManager();
    $block = new \WebEtDesign\CmsBundle\Entity\CmsSharedBlock();
    foreach ([['TEXT', 'one'], ['TEXTAREA', 'two'], ['WYSIWYG', '<p>three</p>'], ['CUSTOM', 'four'], ['NO_INDEX', 'ignored'], ['UNKNOWN', 'ignored']] as [$type, $value]) {
        $block->addContent((new \WebEtDesign\CmsBundle\Entity\CmsContent())->setType($type)->setValue($value));
    }
    $listener->postLoad(new PostLoadEventArgs($block, $em));
    check($block->indexedContent === 'one two <p>three</p> custom:four ', 'Text/custom index changed');
    $empty = new \WebEtDesign\CmsBundle\Entity\CmsSharedBlock();
    $listener->postLoad(new PostLoadEventArgs($empty, $em));
    check($empty->indexedContent === '', 'Empty index changed');
    $listener->postLoad(new PostLoadEventArgs(new stdClass(), $em));
    check($em->persisted === [] && $em->flushes === 0, 'postLoad wrote entities');
    // ORM 2 aliases would otherwise hide the removed parameter type.
    $parameter = (new ReflectionMethod($listener, 'postLoad'))->getParameters()[0];
    check((string) $parameter->getType() === PostLoadEventArgs::class, 'postLoad must accept dedicated PostLoadEventArgs, not removed LifecycleEventArgs');
};

$tests['menu-ignore'] = static function (): void {
    $listener = new \WebEtDesign\CmsBundle\EventListener\MenuAdminListener();
    $em = new RecordingEntityManager();
    $menu = new \WebEtDesign\CmsBundle\Entity\CmsMenu();
    $menu->initRoot = false;
    // Persistence is the actual base class of ORM 3 event args; unlike ORM 2
    // it has no getEntity/getEntityManager aliases. No fake vendor classes.
    foreach ([new stdClass(), $menu] as $object) {
        $listener->postPersist(new PersistenceEventArgs($object, $em));
        $listener->postPersist(new PostPersistEventArgs($object, $em));
    }
    check($em->persisted === [] && $em->flushes === 0, 'Ignored/disabled menu wrote entities');
};

$tests['menu-root'] = static function (): void {
    foreach ([PersistenceEventArgs::class, PostPersistEventArgs::class] as $eventClass) {
        $em = new RecordingEntityManager();
        $site = (new \WebEtDesign\CmsBundle\Entity\CmsSite())->setLabel('Test site');
        $menu = (new \WebEtDesign\CmsBundle\Entity\CmsMenu())->setLabel('Navigation')->setSite($site);
        (new \WebEtDesign\CmsBundle\EventListener\MenuAdminListener())->postPersist(new $eventClass($menu, $em));
        check(count($em->persisted) === 1 && $em->flushes === 1, 'Root persistence/flush request changed');
        $item = $em->persisted[0];
        check($item instanceof \WebEtDesign\CmsBundle\Entity\CmsMenuItem, 'Root item missing');
        check($item->getName() === 'root Test site Navigation' && $item->getMenu() === $menu, 'Root name/menu changed');
    }
};

$tests['site-initialization'] = static function (): void {
    // Only prePersist is exercised: router/filesystem/kernel dependencies are
    // deliberately not constructed, so cache/filesystem side effects are impossible.
    $reflection = new ReflectionClass(\WebEtDesign\CmsBundle\EventListener\SiteAdminListener::class);
    $listener = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('siteClass')->setValue($listener, \WebEtDesign\CmsBundle\Entity\CmsSite::class);
    $reflection->getProperty('parameterBag')->setValue($listener, new ParameterBag(['wd_cms.cms' => ['default_home_template' => 'HOME']]));
    foreach ([PersistenceEventArgs::class, PrePersistEventArgs::class] as $eventClass) {
        $ignored = new RecordingEntityManager();
        $listener->prePersist(new $eventClass(new stdClass(), $ignored));
        check($ignored->persisted === [] && $ignored->flushes === 0, 'Unrelated site event wrote entities');
        foreach ([[true, true], [true, false], [false, true], [false, false]] as [$initPage, $initMenu]) {
            foreach ([null, 'custom'] as $filter) {
                $em = new RecordingEntityManager();
                $site = (new \WebEtDesign\CmsBundle\Entity\CmsSite())->setLabel('Test site');
                $site->setTemplateFilter($filter);
                $site->initPage = $initPage;
                $site->initMenu = $initMenu;
                $listener->prePersist(new $eventClass($site, $em));
                check(count($em->persisted) === ($initPage ? 1 : 0) + ($initMenu ? 2 : 0), 'Initial entity count changed');
                check($em->flushes === 0, 'Site prePersist requested flush');
                check(count($site->getPages()) === ($initPage ? 1 : 0), 'Page initialization flag ignored');
                check(count($site->getMenus()) === ($initMenu ? 1 : 0), 'Menu initialization flag ignored');
                if ($initPage) {
                    $page = $site->getPages()->first();
                    check($page === $em->persisted[0] && $page->getSite() === $site, 'Homepage linkage changed');
                    check($page->getTemplate() === ($filter ? 'custom_HOME' : 'HOME'), 'Homepage template changed');
                    check($page->getTitle() === 'Homepage' && $page->isActive() && $page->rootPage, 'Homepage defaults changed');
                }
                if ($initMenu) {
                    $menu = $site->getMenus()->first();
                    [$root, $home] = array_slice($em->persisted, $initPage ? 1 : 0);
                    check($menu->getCode() === 'main_menu' && $menu->getLabel() === 'Menu principal', 'Initial menu defaults changed');
                    check($menu->getSite() === $site && $menu->getType() === \WebEtDesign\CmsBundle\Entity\CmsMenuTypeEnum::DEFAULT, 'Initial menu linkage/type changed');
                    check($root->getName() === 'root Test site Menu principal' && $root->getRoot() === $root && $root->getMenu() === $menu, 'Initial menu root changed');
                    check($home->getName() === 'Homepage' && $home->getParent() === $root && $home->getMenu() === $menu, 'Initial homepage menu item changed');
                }
            }
        }
    }
};

$failures = $executed = 0;
foreach ($tests as $name => $test) {
    if (isset($argv[2]) && $argv[2] !== $name) {
        continue;
    }
    ++$executed;
    try {
        $test();
        print "PASS: $name\n";
    } catch (Throwable $exception) {
        ++$failures;
        fwrite(STDERR, "FAIL: $name: " . $exception::class . ': ' . $exception->getMessage() . "\n");
    }
}
check($executed > 0, 'Unknown test name');
printf("Lifecycle regression: %d tests, %d failures; no database connection or real flush\n", $executed, $failures);
exit($failures === 0 ? 0 : 1);
