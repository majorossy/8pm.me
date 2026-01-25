<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Unit\Console\Command;

use ArchiveDotOrg\Core\Api\Data\ImportResultInterface;
use ArchiveDotOrg\Core\Api\ShowImporterInterface;
use ArchiveDotOrg\Core\Console\Command\ImportShowsCommand;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Framework\App\State;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unit tests for ImportShowsCommand
 *
 * @covers \ArchiveDotOrg\Core\Console\Command\ImportShowsCommand
 */
class ImportShowsCommandTest extends TestCase
{
    private ImportShowsCommand $command;
    private ShowImporterInterface|MockObject $showImporterMock;
    private Config|MockObject $configMock;
    private State|MockObject $stateMock;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->showImporterMock = $this->createMock(ShowImporterInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->stateMock = $this->createMock(State::class);

        $this->configMock->method('isEnabled')->willReturn(true);

        $this->command = new ImportShowsCommand(
            $this->showImporterMock,
            $this->configMock,
            $this->stateMock
        );

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @test
     */
    public function commandHasCorrectName(): void
    {
        $this->assertEquals('archive:import:shows', $this->command->getName());
    }

    /**
     * @test
     */
    public function commandHasRequiredArguments(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('artist'));
        $this->assertTrue($definition->getArgument('artist')->isRequired());
    }

    /**
     * @test
     */
    public function commandHasExpectedOptions(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('collection'));
        $this->assertTrue($definition->hasOption('limit'));
        $this->assertTrue($definition->hasOption('offset'));
        $this->assertTrue($definition->hasOption('dry-run'));
    }

    /**
     * @test
     */
    public function executeFailsWhenModuleDisabled(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->method('isEnabled')->willReturn(false);

        $command = new ImportShowsCommand(
            $this->showImporterMock,
            $this->configMock,
            $this->stateMock
        );

        $application = new Application();
        $application->add($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['artist' => 'Test Artist']);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('disabled', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function executeValidatesEmptyArtistName(): void
    {
        $exitCode = $this->commandTester->execute(['artist' => '   ']);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Artist name must be a non-empty string', $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function executeValidatesCollectionIdFormat(): void
    {
        $exitCode = $this->commandTester->execute([
            'artist' => 'Test Artist',
            '--collection' => 'invalid collection id with spaces!'
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('alphanumeric characters, underscores, and hyphens', $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function executeAcceptsValidCollectionId(): void
    {
        $this->configMock->method('getCollectionIdForArtist')->willReturn(null);

        $resultMock = $this->createMock(ImportResultInterface::class);
        $resultMock->method('hasErrors')->willReturn(false);
        $resultMock->method('toArray')->willReturn([
            'shows_processed' => 0,
            'tracks_created' => 0,
            'tracks_updated' => 0,
            'tracks_skipped' => 0,
            'total_tracks' => 0,
            'error_count' => 0,
            'errors' => [],
            'duration_seconds' => 1
        ]);

        $this->showImporterMock->method('importByCollection')->willReturn($resultMock);

        $exitCode = $this->commandTester->execute([
            'artist' => 'Test Artist',
            '--collection' => 'Valid_Collection-ID123'
        ]);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     */
    public function executeValidatesLimitIsPositiveInteger(): void
    {
        $exitCode = $this->commandTester->execute([
            'artist' => 'Test Artist',
            '--collection' => 'TestCollection',
            '--limit' => '-5'
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Limit must be a positive integer', $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function executeValidatesLimitIsNotZero(): void
    {
        $exitCode = $this->commandTester->execute([
            'artist' => 'Test Artist',
            '--collection' => 'TestCollection',
            '--limit' => '0'
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Limit must be a positive integer', $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function executeValidatesOffsetIsNonNegative(): void
    {
        $exitCode = $this->commandTester->execute([
            'artist' => 'Test Artist',
            '--collection' => 'TestCollection',
            '--offset' => '-1'
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Offset must be a non-negative integer', $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function executeAcceptsZeroOffset(): void
    {
        $this->configMock->method('getCollectionIdForArtist')->willReturn(null);

        $resultMock = $this->createMock(ImportResultInterface::class);
        $resultMock->method('hasErrors')->willReturn(false);
        $resultMock->method('toArray')->willReturn([
            'shows_processed' => 0,
            'tracks_created' => 0,
            'tracks_updated' => 0,
            'tracks_skipped' => 0,
            'total_tracks' => 0,
            'error_count' => 0,
            'errors' => [],
            'duration_seconds' => 1
        ]);

        $this->showImporterMock->method('importByCollection')->willReturn($resultMock);

        $exitCode = $this->commandTester->execute([
            'artist' => 'Test Artist',
            '--collection' => 'TestCollection',
            '--offset' => '0'
        ]);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     */
    public function executeFailsWhenNoCollectionConfigured(): void
    {
        $this->configMock->method('getCollectionIdForArtist')
            ->with('Unknown Artist')
            ->willReturn(null);

        $exitCode = $this->commandTester->execute([
            'artist' => 'Unknown Artist'
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('No collection ID provided', $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function executeUsesConfiguredCollectionWhenNotProvided(): void
    {
        $configuredCollectionId = 'ConfiguredCollection';

        $this->configMock->method('getCollectionIdForArtist')
            ->with('Test Artist')
            ->willReturn($configuredCollectionId);

        $resultMock = $this->createMock(ImportResultInterface::class);
        $resultMock->method('hasErrors')->willReturn(false);
        $resultMock->method('toArray')->willReturn([
            'shows_processed' => 5,
            'tracks_created' => 50,
            'tracks_updated' => 0,
            'tracks_skipped' => 0,
            'total_tracks' => 50,
            'error_count' => 0,
            'errors' => [],
            'duration_seconds' => 10
        ]);

        $this->showImporterMock->expects($this->once())
            ->method('importByCollection')
            ->with('Test Artist', $configuredCollectionId, $this->anything(), $this->anything(), $this->anything())
            ->willReturn($resultMock);

        $exitCode = $this->commandTester->execute(['artist' => 'Test Artist']);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     */
    public function executeDryRunCallsImporterDryRun(): void
    {
        $this->configMock->method('getCollectionIdForArtist')->willReturn('TestCollection');

        $resultMock = $this->createMock(ImportResultInterface::class);
        $resultMock->method('toArray')->willReturn([
            'shows_processed' => 3,
            'tracks_created' => 30,
            'tracks_updated' => 5,
            'tracks_skipped' => 2,
            'total_tracks' => 37,
            'error_count' => 0,
            'errors' => [],
            'duration_seconds' => null
        ]);

        $this->showImporterMock->expects($this->once())
            ->method('dryRun')
            ->with('Test Artist', 'TestCollection', null, null)
            ->willReturn($resultMock);

        // importByCollection should NOT be called during dry run
        $this->showImporterMock->expects($this->never())
            ->method('importByCollection');

        $exitCode = $this->commandTester->execute([
            'artist' => 'Test Artist',
            '--dry-run' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Dry Run', $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function executeDisplaysResultsTable(): void
    {
        $this->configMock->method('getCollectionIdForArtist')->willReturn('TestCollection');

        $resultMock = $this->createMock(ImportResultInterface::class);
        $resultMock->method('hasErrors')->willReturn(false);
        $resultMock->method('toArray')->willReturn([
            'shows_processed' => 10,
            'tracks_created' => 100,
            'tracks_updated' => 25,
            'tracks_skipped' => 5,
            'total_tracks' => 130,
            'error_count' => 0,
            'errors' => [],
            'duration_seconds' => 60
        ]);

        $this->showImporterMock->method('importByCollection')->willReturn($resultMock);

        $exitCode = $this->commandTester->execute(['artist' => 'Test Artist']);

        $output = $this->commandTester->getDisplay();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Results', $output);
        $this->assertStringContainsString('Shows Processed', $output);
        $this->assertStringContainsString('10', $output);
        $this->assertStringContainsString('Tracks Created', $output);
        $this->assertStringContainsString('100', $output);
    }

    /**
     * @test
     */
    public function executeReturnsFailureOnImportErrors(): void
    {
        $this->configMock->method('getCollectionIdForArtist')->willReturn('TestCollection');

        $resultMock = $this->createMock(ImportResultInterface::class);
        $resultMock->method('hasErrors')->willReturn(true);
        $resultMock->method('toArray')->willReturn([
            'shows_processed' => 5,
            'tracks_created' => 40,
            'tracks_updated' => 0,
            'tracks_skipped' => 10,
            'total_tracks' => 50,
            'error_count' => 3,
            'errors' => [
                ['context' => 'show1', 'message' => 'Error 1'],
                ['context' => 'show2', 'message' => 'Error 2'],
                ['context' => 'show3', 'message' => 'Error 3']
            ],
            'duration_seconds' => 30
        ]);

        $this->showImporterMock->method('importByCollection')->willReturn($resultMock);

        $exitCode = $this->commandTester->execute(['artist' => 'Test Artist']);

        $output = $this->commandTester->getDisplay();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('with errors', $output);
        $this->assertStringContainsString('Error 1', $output);
    }

    /**
     * @test
     */
    public function executePassesLimitAndOffsetToImporter(): void
    {
        $this->configMock->method('getCollectionIdForArtist')->willReturn('TestCollection');

        $resultMock = $this->createMock(ImportResultInterface::class);
        $resultMock->method('hasErrors')->willReturn(false);
        $resultMock->method('toArray')->willReturn([
            'shows_processed' => 5,
            'tracks_created' => 50,
            'tracks_updated' => 0,
            'tracks_skipped' => 0,
            'total_tracks' => 50,
            'error_count' => 0,
            'errors' => [],
            'duration_seconds' => 10
        ]);

        $this->showImporterMock->expects($this->once())
            ->method('importByCollection')
            ->with(
                'Test Artist',
                'TestCollection',
                10,  // limit
                5,   // offset
                $this->anything()
            )
            ->willReturn($resultMock);

        $this->commandTester->execute([
            'artist' => 'Test Artist',
            '--limit' => '10',
            '--offset' => '5'
        ]);
    }

    /**
     * @test
     */
    public function executeTrimsArtistName(): void
    {
        $this->configMock->method('getCollectionIdForArtist')
            ->with('Test Artist') // Should be trimmed
            ->willReturn('TestCollection');

        $resultMock = $this->createMock(ImportResultInterface::class);
        $resultMock->method('hasErrors')->willReturn(false);
        $resultMock->method('toArray')->willReturn([
            'shows_processed' => 0,
            'tracks_created' => 0,
            'tracks_updated' => 0,
            'tracks_skipped' => 0,
            'total_tracks' => 0,
            'error_count' => 0,
            'errors' => [],
            'duration_seconds' => 1
        ]);

        $this->showImporterMock->expects($this->once())
            ->method('importByCollection')
            ->with('Test Artist', $this->anything(), $this->anything(), $this->anything(), $this->anything())
            ->willReturn($resultMock);

        $this->commandTester->execute(['artist' => '  Test Artist  ']);
    }

    /**
     * @test
     */
    public function executeHandlesImporterException(): void
    {
        $this->configMock->method('getCollectionIdForArtist')->willReturn('TestCollection');

        $this->showImporterMock->method('importByCollection')
            ->willThrowException(new \Exception('Import failed: connection timeout'));

        $exitCode = $this->commandTester->execute(['artist' => 'Test Artist']);

        $output = $this->commandTester->getDisplay();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Import failed', $output);
    }
}
