<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Model\Cache\ApiResponseCache;
use ArchiveDotOrg\Core\Model\Resilience\CircuitBreaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to manage Archive.org API cache and circuit breaker
 */
class CacheClearCommand extends Command
{
    private ApiResponseCache $apiCache;
    private CircuitBreaker $circuitBreaker;

    public function __construct(
        ApiResponseCache $apiCache,
        CircuitBreaker $circuitBreaker,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->apiCache = $apiCache;
        $this->circuitBreaker = $circuitBreaker;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('archive:cache:clear')
            ->setDescription('Clear Archive.org API cache and/or reset circuit breaker')
            ->addOption(
                'cache',
                'c',
                InputOption::VALUE_NONE,
                'Clear API response cache'
            )
            ->addOption(
                'circuit',
                'r',
                InputOption::VALUE_NONE,
                'Reset circuit breaker to closed state'
            )
            ->addOption(
                'status',
                's',
                InputOption::VALUE_NONE,
                'Show cache and circuit breaker status'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $clearCache = $input->getOption('cache');
        $resetCircuit = $input->getOption('circuit');
        $showStatus = $input->getOption('status');

        // If no options specified, show status by default
        if (!$clearCache && !$resetCircuit && !$showStatus) {
            $showStatus = true;
        }

        if ($showStatus) {
            $this->showStatus($output);
        }

        if ($clearCache) {
            $this->apiCache->clear();
            $output->writeln('<info>API response cache cleared.</info>');
        }

        if ($resetCircuit) {
            $this->circuitBreaker->reset();
            $output->writeln('<info>Circuit breaker reset to closed state.</info>');
        }

        return Command::SUCCESS;
    }

    /**
     * Display current status
     */
    private function showStatus(OutputInterface $output): void
    {
        $output->writeln('<comment>Archive.org API Status</comment>');
        $output->writeln('');

        // Circuit breaker status
        $circuitStatus = $this->circuitBreaker->getStatus();
        $stateColor = match ($circuitStatus['state']) {
            CircuitBreaker::STATE_CLOSED => 'info',
            CircuitBreaker::STATE_HALF_OPEN => 'comment',
            CircuitBreaker::STATE_OPEN => 'error',
            default => 'info',
        };

        $output->writeln('Circuit Breaker:');
        $output->writeln(sprintf(
            '  State: <%s>%s</%s>',
            $stateColor,
            strtoupper($circuitStatus['state']),
            $stateColor
        ));
        $output->writeln(sprintf('  Failure count: %d / %d', $circuitStatus['failures'], $circuitStatus['threshold']));

        if ($circuitStatus['last_failure'] > 0) {
            $lastFailure = date('Y-m-d H:i:s', $circuitStatus['last_failure']);
            $output->writeln(sprintf('  Last failure: %s', $lastFailure));

            if ($circuitStatus['state'] === CircuitBreaker::STATE_OPEN) {
                $resetTime = $circuitStatus['last_failure'] + $circuitStatus['reset_seconds'];
                $secondsUntilReset = max(0, $resetTime - time());
                $output->writeln(sprintf('  Reset in: %d seconds', $secondsUntilReset));
            }
        }

        $output->writeln('');
        $output->writeln('Use --cache to clear API cache, --circuit to reset circuit breaker');
    }
}
