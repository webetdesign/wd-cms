<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Command;

use Doctrine\DBAL\Connection;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cms:normalize-legacy-json',
    description: 'Convert legacy PHP-serialized arrays in CMS JSON columns without hydrating Doctrine entities.',
)]
final class CmsNormalizeLegacyJsonCommand extends Command
{
    /** @var array<string, array{table: string, column: string}> */
    private const JSON_COLUMNS = [
        'CMS page roles' => ['table' => 'cms__page', 'column' => 'roles'],
        'CMS route methods' => ['table' => 'cms__route', 'column' => 'methods'],
        'CMS menu item parameters' => ['table' => 'cms__menu_item', 'column' => 'params'],
    ];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Persist the conversions. Without this option, only report the changes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $changes = [];
        $invalidValues = [];

        foreach (self::JSON_COLUMNS as $label => $definition) {
            $rows = $this->connection->fetchAllAssociative(sprintf(
                'SELECT id, %1$s AS value FROM %2$s WHERE %1$s IS NOT NULL',
                $definition['column'],
                $definition['table'],
            ));

            foreach ($rows as $row) {
                $value = $row['value'];

                if (!is_string($value) || $this->isJson($value)) {
                    continue;
                }

                $normalizedValue = $this->normalizeSerializedArray($value);
                if ($normalizedValue === null) {
                    $invalidValues[] = sprintf('%s #%s', $label, $row['id']);
                    continue;
                }

                $changes[] = [
                    'label' => $label,
                    'table' => $definition['table'],
                    'column' => $definition['column'],
                    'id' => $row['id'],
                    'value' => $normalizedValue,
                ];
            }
        }

        if ($changes === []) {
            $io->success('No legacy serialized JSON values found.');

            return Command::SUCCESS;
        }

        $summary = [];
        foreach ($changes as $change) {
            $column = sprintf('%s (%s.%s)', $change['label'], $change['table'], $change['column']);
            $summary[$column] = ($summary[$column] ?? 0) + 1;
        }

        $io->table(
            ['Column', 'Rows'],
            array_map(
                static fn (string $column, int $rows): array => [$column, $rows],
                array_keys($summary),
                $summary,
            ),
        );

        if ($invalidValues !== []) {
            $io->warning(sprintf('Skipped non-JSON values that are not serialized arrays: %s', implode(', ', $invalidValues)));
        }

        if (!$input->getOption('apply')) {
            $io->note(sprintf('%d row(s) would be converted. Run again with --apply to persist them.', count($changes)));

            return Command::SUCCESS;
        }

        $this->connection->beginTransaction();

        try {
            foreach ($changes as $change) {
                $this->connection->update(
                    $change['table'],
                    [$change['column'] => $change['value']],
                    ['id' => $change['id']],
                );
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }

        $io->success(sprintf('%d row(s) converted to JSON.', count($changes)));

        return Command::SUCCESS;
    }

    private function isJson(string $value): bool
    {
        try {
            json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            return true;
        } catch (JsonException) {
            return false;
        }
    }

    private function normalizeSerializedArray(string $value): ?string
    {
        try {
            $data = @unserialize($value, ['allowed_classes' => false]);
            if (!is_array($data)) {
                return null;
            }

            return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            return null;
        }
    }
}
