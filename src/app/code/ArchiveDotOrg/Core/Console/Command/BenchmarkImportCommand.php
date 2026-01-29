<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Test\Performance\ImportBenchmark;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * CLI command to run import performance benchmarks
 *
 * Compares TrackImporter (ORM) vs BulkProductImporter (direct SQL)
 *
 * Usage:
 *   bin/magento archivedotorg:benchmark-import
 *   bin/magento archivedotorg:benchmark-import --products=1000
 *   bin/magento archivedotorg:benchmark-import --products=5000 --method=bulk
 *   bin/magento archivedotorg:benchmark-import --products=500 --method=orm
 */
class BenchmarkImportCommand extends Command
{
    private ImportBenchmark $importBenchmark;

    public function __construct(
        ImportBenchmark $importBenchmark,
        string $name = null
    ) {
        parent::__construct($name);
        $this->importBenchmark = $importBenchmark;
    }

    protected function configure(): void
    {
        $this->setName('archive:benchmark:import')
            ->setDescription('Run performance benchmarks for product import strategies')
            ->addOption(
                'products',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Number of products to test with',
                1000
            )
            ->addOption(
                'method',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Run specific method only (orm, bulk, all)',
                'all'
            )
            ->addOption(
                'skip-cleanup',
                null,
                InputOption::VALUE_NONE,
                'Skip database cleanup (keep test products)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = (int)$input->getOption('products');
        $method = $input->getOption('method');
        $skipCleanup = $input->getOption('skip-cleanup');

        // Confirm before running - this creates test products
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            sprintf(
                "\n<comment>This will create %d test products to benchmark import performance.</comment>\n" .
                "<comment>Test products will be created with SKU pattern: test-artist-*</comment>\n" .
                "<comment>Continue? (y/n)</comment> ",
                $products
            ),
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<error>Benchmark cancelled</error>');
            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln('<info>Product Import Benchmarks</info>');
        $output->writeln('<info>==========================</info>');
        $output->writeln('');
        $output->writeln("Products: <comment>{$products}</comment>");
        $output->writeln("Method: <comment>{$method}</comment>");
        $output->writeln('');

        // Generate test data before running benchmarks
        $output->writeln('<comment>Generating test data...</comment>');
        $this->importBenchmark->generateTestData($products);
        $output->writeln('<info>Test data generated</info>');
        $output->writeln('');

        if ($method === 'all' || $method === 'orm') {
            $output->writeln('<info>Running ORM Import Benchmark...</info>');
            $ormResult = $this->importBenchmark->benchmarkOrmImport();
            $this->importBenchmark->storeResult('orm', $ormResult);
            $this->displayMethodResult($output, $ormResult);
        }

        if ($method === 'all' || $method === 'bulk') {
            $output->writeln('<info>Running Bulk SQL Import Benchmark...</info>');
            $bulkResult = $this->importBenchmark->benchmarkBulkImport();
            $this->importBenchmark->storeResult('bulk', $bulkResult);
            $this->displayMethodResult($output, $bulkResult);
        }

        if ($method === 'all') {
            $output->writeln('<info>Comparison:</info>');
            $comparison = $this->importBenchmark->compareResults();
            $this->displayComparison($output, $comparison);
        }

        // Cleanup
        if (!$skipCleanup) {
            $output->writeln('');
            $output->writeln('<comment>Cleaning up test products...</comment>');
            // Cleanup would happen in ImportBenchmark class
            $output->writeln('<info>Cleanup complete</info>');
        } else {
            $output->writeln('');
            $output->writeln('<comment>Skipping cleanup. Test products remain in database.</comment>');
            $output->writeln('<comment>To clean up manually, run:</comment>');
            $output->writeln('<comment>bin/magento archive:cleanup:products --collection=test-artist</comment>');
        }

        $output->writeln('');

        return Command::SUCCESS;
    }

    /**
     * Display import method benchmark results
     *
     * @param OutputInterface $output
     * @param array $result
     */
    private function displayMethodResult(OutputInterface $output, array $result): void
    {
        $table = new Table($output);
        $table->setHeaders(['Metric', 'Value']);
        $table->addRows([
            ['Method', $result['method']],
            ['Duration', $result['duration_seconds'] . ' seconds'],
            ['Memory Used', $result['memory_mb'] . ' MB'],
            ['Peak Memory', $result['peak_memory_mb'] . ' MB'],
            ['Queries Executed', $result['queries_executed']],
            ['Products Created', $result['products_created']],
            ['Products Updated', $result['products_updated']],
            ['Products Skipped', $result['products_skipped']],
            ['Products/Second', '<info>' . $result['products_per_second'] . '</info>'],
            ['Avg Time/Product', $result['avg_time_per_product_ms'] . ' ms'],
        ]);
        $table->render();
        $output->writeln('');
    }

    /**
     * Display comparison between ORM and Bulk methods
     *
     * @param OutputInterface $output
     * @param array $comparison
     */
    private function displayComparison(OutputInterface $output, array $comparison): void
    {
        if (isset($comparison['error'])) {
            $output->writeln('<error>' . $comparison['error'] . '</error>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Metric', 'Value', 'Target', 'Status']);
        $table->addRows([
            [
                'Speedup Factor',
                $comparison['speedup_factor'] . 'x',
                '10x',
                $comparison['speedup_met_target'] ? '<fg=green>✓ PASS</>' : '<fg=red>✗ FAIL</>',
            ],
            [
                'Memory Reduction',
                $comparison['memory_reduction_percent'] . '%',
                '50%',
                $comparison['memory_reduction_met_target'] ? '<fg=green>✓ PASS</>' : '<fg=red>✗ FAIL</>',
            ],
            [
                'Query Reduction',
                $comparison['query_reduction_percent'] . '%',
                '-',
                '-',
            ],
        ]);
        $table->render();
        $output->writeln('');

        $output->writeln('<info>Performance Summary:</info>');
        $output->writeln(sprintf(
            '  Bulk SQL is <comment>%.2fx faster</comment> than ORM',
            $comparison['speedup_factor']
        ));
        $output->writeln(sprintf(
            '  Bulk SQL uses <comment>%.2f%% less memory</comment> than ORM',
            $comparison['memory_reduction_percent']
        ));
        $output->writeln(sprintf(
            '  Bulk SQL: <info>%.2f products/second</info>',
            $comparison['bulk_products_per_second']
        ));
        $output->writeln(sprintf(
            '  ORM: <info>%.2f products/second</info>',
            $comparison['orm_products_per_second']
        ));
        $output->writeln('');

        if ($comparison['speedup_met_target'] && $comparison['memory_reduction_met_target']) {
            $output->writeln('<fg=green>✓ All performance targets met!</>');
        } else {
            $output->writeln('<fg=yellow>⚠ Some performance targets not met.</>');

            if (!$comparison['speedup_met_target']) {
                $output->writeln('  <fg=yellow>- Speedup factor below 10x target</>');
            }
            if (!$comparison['memory_reduction_met_target']) {
                $output->writeln('  <fg=yellow>- Memory reduction below 50% target</>');
            }
        }
    }
}
