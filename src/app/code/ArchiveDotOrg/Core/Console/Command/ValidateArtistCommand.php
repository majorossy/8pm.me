<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\ArtistConfigLoaderInterface;
use ArchiveDotOrg\Core\Api\ArtistConfigValidatorInterface;
use ArchiveDotOrg\Core\Exception\ConfigurationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to validate artist YAML configuration files.
 *
 * Usage:
 *   bin/magento archive:validate lettuce
 *   bin/magento archive:validate --all
 */
class ValidateArtistCommand extends Command
{
    private const ARGUMENT_ARTIST = 'artist';
    private const OPTION_ALL = 'all';

    /**
     * @param ArtistConfigLoaderInterface $configLoader
     * @param ArtistConfigValidatorInterface $validator
     * @param string|null $name
     */
    public function __construct(
        private readonly ArtistConfigLoaderInterface $configLoader,
        private readonly ArtistConfigValidatorInterface $validator,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('archive:validate')
            ->setDescription('Validate artist YAML configuration')
            ->addArgument(
                self::ARGUMENT_ARTIST,
                InputArgument::OPTIONAL,
                'Artist key to validate (e.g., lettuce, phish)'
            )
            ->addOption(
                self::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Validate all artist configurations'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $artistKey = $input->getArgument(self::ARGUMENT_ARTIST);
        $validateAll = $input->getOption(self::OPTION_ALL);

        if ($validateAll) {
            return $this->validateAll($output);
        }

        if (empty($artistKey)) {
            $output->writeln('<error>Either provide an artist key or use --all flag</error>');
            $output->writeln('Usage: bin/magento archive:validate <artist> OR archive:validate --all');
            return Command::FAILURE;
        }

        return $this->validateSingle($artistKey, $output);
    }

    /**
     * Validate all artist configurations.
     *
     * @param OutputInterface $output
     * @return int
     */
    private function validateAll(OutputInterface $output): int
    {
        $artists = $this->configLoader->getAvailableArtists();

        if (empty($artists)) {
            $output->writeln('<comment>No artist configuration files found</comment>');
            return Command::SUCCESS;
        }

        $output->writeln(sprintf('<info>Validating %d artist configurations...</info>', count($artists)));
        $output->writeln('');

        $totalErrors = 0;
        $totalWarnings = 0;
        $failed = [];

        foreach ($artists as $artistKey) {
            $result = $this->validateSingle($artistKey, $output, false);

            if ($result !== Command::SUCCESS) {
                $failed[] = $artistKey;
            }
        }

        $output->writeln('');
        $output->writeln(str_repeat('=', 60));

        if (empty($failed)) {
            $output->writeln(sprintf(
                '<info>✓ All %d artist configurations are valid</info>',
                count($artists)
            ));
            return Command::SUCCESS;
        }

        $output->writeln(sprintf(
            '<error>✗ %d/%d artist configurations have errors</error>',
            count($failed),
            count($artists)
        ));
        $output->writeln(sprintf('<comment>Failed: %s</comment>', implode(', ', $failed)));

        return Command::FAILURE;
    }

    /**
     * Validate a single artist configuration.
     *
     * @param string $artistKey
     * @param OutputInterface $output
     * @param bool $verbose
     * @return int
     */
    private function validateSingle(string $artistKey, OutputInterface $output, bool $verbose = true): int
    {
        if ($verbose) {
            $output->writeln(sprintf('<info>Validating %s configuration...</info>', $artistKey));
            $output->writeln('');
        }

        try {
            // Load will throw exception if file doesn't exist or is invalid YAML
            $config = $this->configLoader->load($artistKey);

            // Validate (loader already does this, but we'll do it again for explicit output)
            $result = $this->validator->validate($config);

            // Display errors
            if (!empty($result['errors'])) {
                $output->writeln(sprintf('<error>Errors (%d):</error>', count($result['errors'])));
                foreach ($result['errors'] as $error) {
                    $output->writeln('  <fg=red>✗</> ' . $error);
                }
                $output->writeln('');
            }

            // Display warnings
            if (!empty($result['warnings'])) {
                $output->writeln(sprintf('<comment>Warnings (%d):</comment>', count($result['warnings'])));
                foreach ($result['warnings'] as $warning) {
                    $output->writeln('  <fg=yellow>⚠</> ' . $warning);
                }
                $output->writeln('');
            }

            // Summary
            if ($result['valid']) {
                if ($verbose) {
                    if (!empty($result['warnings'])) {
                        $output->writeln('<info>Validation PASSED with warnings</info>');
                    } else {
                        $output->writeln('<info>✓ Validation PASSED</info>');
                    }
                } else {
                    $output->write(sprintf('<info>✓ %s</info> ', $artistKey));
                }
                return Command::SUCCESS;
            }

            if ($verbose) {
                $output->writeln('<error>✗ Validation FAILED. Fix errors before proceeding.</error>');
            } else {
                $output->write(sprintf('<error>✗ %s</error> ', $artistKey));
            }
            return Command::FAILURE;

        } catch (ConfigurationException $e) {
            $output->writeln(sprintf('<error>Configuration Error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Unexpected Error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
# Test change at Wed Jan 28 21:46:12 EST 2026
