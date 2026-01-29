<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Test\Performance\DashboardBenchmark;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * CLI command to run dashboard query performance benchmarks
 */
class BenchmarkDashboardCommand extends Command
{
    private DashboardBenchmark $dashboardBenchmark;

    public function __construct(
        DashboardBenchmark $dashboardBenchmark,
        string $name = null
    ) {
        $this->dashboardBenchmark = $dashboardBenchmark;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('archivedotorg:benchmark-dashboard')
            ->setDescription('Run performance benchmarks for admin dashboard queries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('<info>Dashboard Query Benchmarks</info>');
        $output->writeln('<info>==========================</info>');
        $output->writeln('');

        $output->writeln('<comment>Running all dashboard query benchmarks...</comment>');
        $output->writeln('');

        $results = $this->dashboardBenchmark->runAll();

        // Artist Grid
        $this->displayQueryResult($output, 'Artist Grid Query', $results['artist_grid'], 100);

        // Import History
        $this->displayQueryResult($output, 'Import History Query', $results['import_history'], 100);

        // Unmatched Tracks
        $this->displayQueryResult($output, 'Unmatched Tracks Query', $results['unmatched_tracks'], 100);

        // Imports Per Day
        $this->displayQueryResult($output, 'Imports Per Day Chart', $results['imports_per_day'], 50);

        // Daily Metrics
        $this->displayQueryResult($output, 'Daily Metrics Aggregation', $results['daily_metrics'], 200);

        // Index Verification
        $output->writeln('<info>Index Verification:</info>');
        $indexTable = new Table($output);
        $indexTable->setHeaders(['Table', 'Index', 'Status']);

        foreach ($results['index_verification'] as $verification) {
            $status = $verification['used'] ? '<info>✓ USED</info>' : '<error>✗ NOT USED</error>';
            $indexTable->addRow([
                $verification['table'],
                $verification['index'],
                $status
            ]);
        }

        $indexTable->render();
        $output->writeln('');

        // Summary
        $allPassed = $this->checkAllTargetsMet($results);

        if ($allPassed) {
            $output->writeln('<info>✓ All performance targets met!</info>');
            $output->writeln('');
            return Command::SUCCESS;
        } else {
            $output->writeln('<error>✗ Some performance targets not met</error>');
            $output->writeln('');
            return Command::FAILURE;
        }
    }

    /**
     * Display query benchmark result
     *
     * @param OutputInterface $output
     * @param string $queryName
     * @param array $result
     * @param int $targetMs
     */
    private function displayQueryResult(OutputInterface $output, string $queryName, array $result, int $targetMs): void
    {
        $durationMs = $result['duration_ms'];
        $passed = $durationMs < $targetMs;
        $status = $passed ? '<info>✓ PASS</info>' : '<error>✗ FAIL</error>';

        $table = new Table($output);
        $table->setHeaders(['Metric', 'Value', 'Target', 'Status']);
        $table->addRows([
            [$queryName, '', '', ''],
            ['Duration', $durationMs . ' ms', "< {$targetMs} ms", $status],
            ['Rows Returned', $result['rows_returned'] ?? 'N/A', '-', '-'],
            ['Queries Executed', $result['queries_executed'] ?? 1, '-', '-'],
            ['Index Used', $result['index_used'] ? 'Yes' : 'No', 'Yes', $result['index_used'] ? '<info>✓</info>' : '<error>✗</error>'],
        ]);
        $table->render();
        $output->writeln('');
    }

    /**
     * Check if all performance targets are met
     *
     * @param array $results
     * @return bool
     */
    private function checkAllTargetsMet(array $results): bool
    {
        $targets = [
            'artist_grid' => 100,
            'import_history' => 100,
            'unmatched_tracks' => 100,
            'imports_per_day' => 50,
            'daily_metrics' => 200,
        ];

        foreach ($targets as $key => $targetMs) {
            if ($results[$key]['duration_ms'] >= $targetMs) {
                return false;
            }
        }

        // Check all indexes are used
        foreach ($results['index_verification'] as $verification) {
            if (!$verification['used']) {
                return false;
            }
        }

        return true;
    }
}
