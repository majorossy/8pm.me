<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Test\Performance\MatchingBenchmark;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * CLI command to run matching algorithm benchmarks
 *
 * Usage:
 *   bin/magento archivedotorg:benchmark-matching
 *   bin/magento archivedotorg:benchmark-matching --tracks=10000
 *   bin/magento archivedotorg:benchmark-matching --tracks=1000 --algorithm=exact
 *   bin/magento archivedotorg:benchmark-matching --tracks=50000 --iterations=10
 */
class BenchmarkMatchingCommand extends Command
{
    private MatchingBenchmark $matchingBenchmark;

    public function __construct(
        MatchingBenchmark $matchingBenchmark,
        string $name = null
    ) {
        parent::__construct($name);
        $this->matchingBenchmark = $matchingBenchmark;
    }

    protected function configure(): void
    {
        $this->setName('archive:benchmark:matching')
            ->setDescription('Run performance benchmarks for track matching algorithms')
            ->addOption(
                'tracks',
                't',
                InputOption::VALUE_OPTIONAL,
                'Number of tracks to test with',
                10000
            )
            ->addOption(
                'iterations',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Number of iterations per test',
                10
            )
            ->addOption(
                'algorithm',
                'a',
                InputOption::VALUE_OPTIONAL,
                'Run specific algorithm only (exact, alias, metaphone, fuzzy, all)',
                'all'
            )
            ->addOption(
                'compare-levenshtein',
                null,
                InputOption::VALUE_NONE,
                'Compare with full Levenshtein (WARNING: slow, max 100 tracks)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tracks = (int)$input->getOption('tracks');
        $iterations = (int)$input->getOption('iterations');
        $algorithm = $input->getOption('algorithm');
        $compareLevenshtein = $input->getOption('compare-levenshtein');

        $output->writeln('');
        $output->writeln('<info>Track Matching Algorithm Benchmarks</info>');
        $output->writeln('<info>====================================</info>');
        $output->writeln('');
        $output->writeln("Tracks: <comment>{$tracks}</comment>");
        $output->writeln("Iterations: <comment>{$iterations}</comment>");
        $output->writeln('');

        if ($compareLevenshtein) {
            return $this->runLevenshteinComparison($output, $tracks);
        }

        $results = $this->matchingBenchmark->runAll($tracks, $iterations);

        $this->displayResults($output, $results, $algorithm);

        return Command::SUCCESS;
    }

    /**
     * Display benchmark results in formatted table
     *
     * @param OutputInterface $output
     * @param array $results
     * @param string $algorithm
     */
    private function displayResults(OutputInterface $output, array $results, string $algorithm): void
    {
        // Index Building
        if ($algorithm === 'all' || $algorithm === 'index') {
            $output->writeln('<info>Index Building Performance:</info>');
            $indexResult = $results['index_build'];

            $table = new Table($output);
            $table->setHeaders(['Metric', 'Value', 'Target', 'Status']);
            $table->addRow([
                'Duration',
                $indexResult['duration_ms'] . ' ms',
                '<5000 ms',
                $indexResult['target_met'] ? '<fg=green>✓ PASS</>' : '<fg=red>✗ FAIL</>',
            ]);
            $table->addRow([
                'Memory',
                $indexResult['memory_mb'] . ' MB',
                '-',
                '-',
            ]);
            $table->addRow([
                'Tracks Indexed',
                $indexResult['tracks'],
                '-',
                '-',
            ]);
            $table->render();
            $output->writeln('');
        }

        // Matching Algorithms
        if ($algorithm === 'all' || $algorithm === 'exact') {
            $this->displayAlgorithmResult($output, 'Exact Match', $results['exact_match'], 100);
        }

        if ($algorithm === 'all' || $algorithm === 'alias') {
            $this->displayAlgorithmResult($output, 'Alias Match', $results['alias_match'], 100);
        }

        if ($algorithm === 'all' || $algorithm === 'metaphone') {
            $this->displayAlgorithmResult($output, 'Metaphone Match', $results['metaphone_match'], 500);
        }

        if ($algorithm === 'all' || $algorithm === 'fuzzy') {
            $this->displayAlgorithmResult($output, 'Fuzzy Match (Top 5)', $results['fuzzy_match'], 2000);
        }

        // Memory Usage
        if ($algorithm === 'all') {
            $output->writeln('<info>Memory Usage:</info>');
            $memoryResult = $results['memory_usage'];

            $table = new Table($output);
            $table->setHeaders(['Metric', 'Value', 'Target', 'Status']);
            $table->addRow([
                'Memory Used',
                $memoryResult['memory_mb'] . ' MB',
                '<50 MB',
                $memoryResult['target_met'] ? '<fg=green>✓ PASS</>' : '<fg=red>✗ FAIL</>',
            ]);
            $table->addRow([
                'Peak Memory',
                $memoryResult['peak_memory_mb'] . ' MB',
                '-',
                '-',
            ]);
            $table->render();
            $output->writeln('');
        }

        // Summary
        $output->writeln('<info>Summary:</info>');
        $allPassed = $results['index_build']['target_met']
            && $results['exact_match']['target_met']
            && $results['metaphone_match']['target_met']
            && $results['fuzzy_match']['target_met']
            && $results['memory_usage']['target_met'];

        if ($allPassed) {
            $output->writeln('<fg=green>✓ All performance targets met!</>');
        } else {
            $output->writeln('<fg=red>✗ Some performance targets not met. Review results above.</>');
        }
        $output->writeln('');
    }

    /**
     * Display individual algorithm result
     *
     * @param OutputInterface $output
     * @param string $name
     * @param array $result
     * @param int $targetMs
     */
    private function displayAlgorithmResult(OutputInterface $output, string $name, array $result, int $targetMs): void
    {
        $output->writeln("<info>{$name}:</info>");

        $table = new Table($output);
        $table->setHeaders(['Metric', 'Value', 'Target', 'Status']);
        $table->addRow([
            'Duration',
            $result['duration_ms'] . ' ms',
            "<{$targetMs} ms",
            $result['target_met'] ? '<fg=green>✓ PASS</>' : '<fg=red>✗ FAIL</>',
        ]);
        $table->addRow([
            'Matches/Iteration',
            $result['matches_per_iteration'],
            '-',
            '-',
        ]);
        $table->addRow([
            'Avg per Match',
            $result['avg_per_match_ms'] . ' ms',
            '-',
            '-',
        ]);

        if (isset($result['note'])) {
            $table->addRow(['Note', $result['note'], '-', '-']);
        }

        $table->render();
        $output->writeln('');
    }

    /**
     * Run comparison with full Levenshtein algorithm
     *
     * WARNING: This is educational only - very slow on large datasets
     *
     * @param OutputInterface $output
     * @param int $tracks
     * @return int
     */
    private function runLevenshteinComparison(OutputInterface $output, int $tracks): int
    {
        if ($tracks > 100) {
            $output->writeln('<error>Cannot compare with full Levenshtein on more than 100 tracks</error>');
            $output->writeln('<error>This would take ~43 hours for 10,000 tracks</error>');
            return Command::FAILURE;
        }

        $output->writeln('<comment>WARNING: Running full Levenshtein comparison</comment>');
        $output->writeln('<comment>This is for educational purposes only</comment>');
        $output->writeln('');

        $result = $this->matchingBenchmark->compareWithFullLevenshtein($tracks);

        if (isset($result['error'])) {
            $output->writeln('<error>' . $result['error'] . '</error>');
            if (isset($result['estimated_time_hours'])) {
                $output->writeln(sprintf(
                    '<error>Estimated time: %.2f hours</error>',
                    $result['estimated_time_hours']
                ));
            }
            return Command::FAILURE;
        }

        $table = new Table($output);
        $table->setHeaders(['Method', 'Duration', 'Speedup']);
        $table->addRow([
            'Limited (Top 5 Candidates)',
            $result['limited_approach_ms'] . ' ms',
            '1x (baseline)',
        ]);
        $table->addRow([
            'Full Levenshtein (All Tracks)',
            $result['full_levenshtein_ms'] . ' ms',
            $result['speedup_factor'] . 'x slower',
        ]);
        $table->render();
        $output->writeln('');

        $output->writeln(sprintf(
            '<comment>For 10,000 tracks, full Levenshtein would take ~%.2f hours</comment>',
            $result['estimated_10k_tracks_hours']
        ));
        $output->writeln('');

        return Command::SUCCESS;
    }
}
