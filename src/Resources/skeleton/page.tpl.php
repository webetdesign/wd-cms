<?= /** @noinspection ALL */
"<?php\n" ?>

namespace <?= $namespace; ?>;

use WebEtDesign\CmsBundle\Attribute\AsCmsPage;
use WebEtDesign\CmsBundle\CmsTemplate\AbstractPage;
<?php foreach ($config['useStatements'] as $value) {?>
use <?= $value; ?>;
<?php }?>

#[AsCmsPage(code: self::CODE)]
class <?= $class_name; ?> extends <?= $parent_class_name; ?><?= "\n" ?>
{
    const CODE = "<?= $config['code']; ?>";
    <?php if (isset($config['route']) && !empty($config['route']) && isset($config['route']['name']) && !empty($config['route']['name'])) {?>
    const ROUTE_NAME = "<?= $config['route']['name']; ?>";
    <?php }?>

    protected ?string $template = '<?= $config['templatePath']; ?>';

    <?php if (!empty($config['label'])) {?>
    protected ?string $label = '<?= $config['label']; ?>';
    <?php }?>

    <?php if (isset($config['route']) && !empty($config['route'])) {?>
    public function getRoute(): ?RouteDefinition
    {
        return RouteDefinition::new()
    <?php if (isset($config['route']['name']) && !empty($config['route']['name'])) {?>
            ->setName(self::ROUTE_NAME)
    <?php }?>
    <?php if (isset($config['route']['path']) && !empty($config['route']['path'])) {?>
            ->setPath('<?= $config['route']['path'] ?>')
    <?php }?>
    <?php if (isset($config['route']['controller']) && !empty($config['route']['controller'])) {?>
            ->setController('<?= $config['route']['controller'] ?>')
    <?php }?>
            ;
    }
    <?php }?>

    public function getBlocks(): iterable
    {
        return [];
    }
}

