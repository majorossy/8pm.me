<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Unit\Model;

use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\AttributeOptionManager;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Model\Entity\Attribute\Source\TableFactory;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AttributeOptionManager
 *
 * @covers \ArchiveDotOrg\Core\Model\AttributeOptionManager
 */
class AttributeOptionManagerTest extends TestCase
{
    private AttributeOptionManager $attributeOptionManager;
    private ProductAttributeRepositoryInterface|MockObject $attributeRepositoryMock;
    private TableFactory|MockObject $tableFactoryMock;
    private AttributeOptionManagementInterface|MockObject $attributeOptionManagementMock;
    private AttributeOptionLabelInterfaceFactory|MockObject $optionLabelFactoryMock;
    private AttributeOptionInterfaceFactory|MockObject $optionFactoryMock;
    private Logger|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->attributeRepositoryMock = $this->createMock(ProductAttributeRepositoryInterface::class);
        $this->tableFactoryMock = $this->createMock(TableFactory::class);
        $this->attributeOptionManagementMock = $this->createMock(AttributeOptionManagementInterface::class);
        $this->optionLabelFactoryMock = $this->createMock(AttributeOptionLabelInterfaceFactory::class);
        $this->optionFactoryMock = $this->createMock(AttributeOptionInterfaceFactory::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->attributeOptionManager = new AttributeOptionManager(
            $this->attributeRepositoryMock,
            $this->tableFactoryMock,
            $this->attributeOptionManagementMock,
            $this->optionLabelFactoryMock,
            $this->optionFactoryMock,
            $this->loggerMock
        );
    }

    private function createAttributeMock(int $attributeId): MockObject
    {
        $attributeMock = $this->createMock(AttributeInterface::class);
        $attributeMock->method('getAttributeId')->willReturn($attributeId);
        return $attributeMock;
    }

    private function createTableSourceMock(array $options): MockObject
    {
        $tableSourceMock = $this->createMock(Table::class);
        $tableSourceMock->method('getAllOptions')->willReturn($options);
        return $tableSourceMock;
    }

    /**
     * @test
     */
    public function getOrCreateOptionIdReturnsExistingOptionId(): void
    {
        $attributeCode = 'show_year';
        $label = '2023';
        $existingOptionId = 42;

        $attributeMock = $this->createAttributeMock(100);
        $this->attributeRepositoryMock->method('get')
            ->with($attributeCode)
            ->willReturn($attributeMock);

        $tableSourceMock = $this->createTableSourceMock([
            ['value' => $existingOptionId, 'label' => '2023'],
            ['value' => 43, 'label' => '2022']
        ]);

        $this->tableFactoryMock->method('create')->willReturn($tableSourceMock);

        // Should not call attributeOptionManagement since option exists
        $this->attributeOptionManagementMock->expects($this->never())->method('add');

        $result = $this->attributeOptionManager->getOrCreateOptionId($attributeCode, $label);

        $this->assertEquals($existingOptionId, $result);
    }

    /**
     * @test
     */
    public function getOrCreateOptionIdCreatesNewOption(): void
    {
        $attributeCode = 'show_year';
        $label = '2024';
        $newOptionId = 99;

        $attributeMock = $this->createAttributeMock(100);
        $this->attributeRepositoryMock->method('get')
            ->with($attributeCode)
            ->willReturn($attributeMock);

        // First call: option doesn't exist
        $tableSourceMock1 = $this->createTableSourceMock([
            ['value' => 42, 'label' => '2023'],
            ['value' => 43, 'label' => '2022']
        ]);

        // Second call: after creation, option exists
        $tableSourceMock2 = $this->createTableSourceMock([
            ['value' => 42, 'label' => '2023'],
            ['value' => 43, 'label' => '2022'],
            ['value' => $newOptionId, 'label' => '2024']
        ]);

        $this->tableFactoryMock->method('create')
            ->willReturnOnConsecutiveCalls($tableSourceMock1, $tableSourceMock2);

        $optionLabelMock = $this->createMock(AttributeOptionLabelInterface::class);
        $this->optionLabelFactoryMock->method('create')->willReturn($optionLabelMock);

        $optionMock = $this->createMock(AttributeOptionInterface::class);
        $this->optionFactoryMock->method('create')->willReturn($optionMock);

        $this->attributeOptionManagementMock->expects($this->once())
            ->method('add')
            ->with(Product::ENTITY, 100, $optionMock);

        $result = $this->attributeOptionManager->getOrCreateOptionId($attributeCode, $label);

        $this->assertEquals($newOptionId, $result);
    }

    /**
     * @test
     */
    public function getOrCreateOptionIdThrowsOnEmptyLabel(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Label for attribute show_year must not be empty');

        $this->attributeOptionManager->getOrCreateOptionId('show_year', '');
    }

    /**
     * @test
     */
    public function getOrCreateOptionIdThrowsOnWhitespaceLabel(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Label for attribute show_year must not be empty');

        $this->attributeOptionManager->getOrCreateOptionId('show_year', '   ');
    }

    /**
     * @test
     */
    public function getOptionIdReturnsNullForNonExistingOption(): void
    {
        $attributeCode = 'show_venue';
        $label = 'Non-Existing Venue';

        $attributeMock = $this->createAttributeMock(100);
        $this->attributeRepositoryMock->method('get')->willReturn($attributeMock);

        $tableSourceMock = $this->createTableSourceMock([
            ['value' => 1, 'label' => 'Red Rocks'],
            ['value' => 2, 'label' => 'Madison Square Garden']
        ]);

        $this->tableFactoryMock->method('create')->willReturn($tableSourceMock);

        $result = $this->attributeOptionManager->getOptionId($attributeCode, $label);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function optionExistsReturnsTrueForExistingOption(): void
    {
        $attributeCode = 'show_venue';
        $label = 'Red Rocks';

        $attributeMock = $this->createAttributeMock(100);
        $this->attributeRepositoryMock->method('get')->willReturn($attributeMock);

        $tableSourceMock = $this->createTableSourceMock([
            ['value' => 1, 'label' => 'Red Rocks']
        ]);

        $this->tableFactoryMock->method('create')->willReturn($tableSourceMock);

        $this->assertTrue($this->attributeOptionManager->optionExists($attributeCode, $label));
    }

    /**
     * @test
     */
    public function optionExistsReturnsFalseForNonExistingOption(): void
    {
        $attributeCode = 'show_venue';
        $label = 'Non-Existing Venue';

        $attributeMock = $this->createAttributeMock(100);
        $this->attributeRepositoryMock->method('get')->willReturn($attributeMock);

        $tableSourceMock = $this->createTableSourceMock([
            ['value' => 1, 'label' => 'Red Rocks']
        ]);

        $this->tableFactoryMock->method('create')->willReturn($tableSourceMock);

        $this->assertFalse($this->attributeOptionManager->optionExists($attributeCode, $label));
    }

    /**
     * @test
     */
    public function getAllOptionsReturnsFormattedOptions(): void
    {
        $attributeCode = 'show_year';

        $attributeMock = $this->createAttributeMock(100);
        $this->attributeRepositoryMock->method('get')->willReturn($attributeMock);

        $tableSourceMock = $this->createTableSourceMock([
            ['value' => 1, 'label' => '2023'],
            ['value' => 2, 'label' => '2022'],
            ['value' => 3, 'label' => '2021']
        ]);

        $this->tableFactoryMock->method('create')->willReturn($tableSourceMock);

        $result = $this->attributeOptionManager->getAllOptions($attributeCode);

        $this->assertCount(3, $result);
        $this->assertEquals(['value' => 1, 'label' => '2023'], $result[0]);
        $this->assertEquals(['value' => 2, 'label' => '2022'], $result[1]);
        $this->assertEquals(['value' => 3, 'label' => '2021'], $result[2]);
    }

    /**
     * @test
     */
    public function clearCacheClearsAllCaches(): void
    {
        $attributeCode = 'show_year';

        $attributeMock = $this->createAttributeMock(100);
        $this->attributeRepositoryMock->method('get')->willReturn($attributeMock);

        $tableSourceMock = $this->createTableSourceMock([
            ['value' => 1, 'label' => '2023']
        ]);

        $this->tableFactoryMock->method('create')->willReturn($tableSourceMock);

        // First call populates cache
        $this->attributeOptionManager->getOptionId($attributeCode, '2023');

        // Clear all caches
        $this->attributeOptionManager->clearCache();

        // Next call should rebuild cache (we can verify by counting factory calls)
        // This test verifies the method doesn't throw
        $result = $this->attributeOptionManager->getOptionId($attributeCode, '2023');
        $this->assertEquals(1, $result);
    }

    /**
     * @test
     */
    public function clearCacheClearsSpecificAttribute(): void
    {
        $attributeMock = $this->createAttributeMock(100);
        $this->attributeRepositoryMock->method('get')->willReturn($attributeMock);

        $tableSourceMock = $this->createTableSourceMock([
            ['value' => 1, 'label' => '2023']
        ]);

        $this->tableFactoryMock->method('create')->willReturn($tableSourceMock);

        // Populate cache
        $this->attributeOptionManager->getOptionId('show_year', '2023');

        // Clear specific attribute cache
        $this->attributeOptionManager->clearCache('show_year');

        // Should work without error
        $result = $this->attributeOptionManager->getOptionId('show_year', '2023');
        $this->assertEquals(1, $result);
    }

    /**
     * @test
     */
    public function bulkGetOrCreateOptionIdsHandlesMixedExistingAndNew(): void
    {
        $attributeCode = 'show_year';
        $labels = ['2022', '2023', '2024']; // 2022, 2023 exist; 2024 is new

        $attributeMock = $this->createAttributeMock(100);
        $this->attributeRepositoryMock->method('get')->willReturn($attributeMock);

        // Initial options
        $tableSourceMock1 = $this->createTableSourceMock([
            ['value' => 1, 'label' => '2022'],
            ['value' => 2, 'label' => '2023']
        ]);

        // After creating 2024
        $tableSourceMock2 = $this->createTableSourceMock([
            ['value' => 1, 'label' => '2022'],
            ['value' => 2, 'label' => '2023'],
            ['value' => 3, 'label' => '2024']
        ]);

        $this->tableFactoryMock->method('create')
            ->willReturnOnConsecutiveCalls($tableSourceMock1, $tableSourceMock2);

        $optionLabelMock = $this->createMock(AttributeOptionLabelInterface::class);
        $this->optionLabelFactoryMock->method('create')->willReturn($optionLabelMock);

        $optionMock = $this->createMock(AttributeOptionInterface::class);
        $this->optionFactoryMock->method('create')->willReturn($optionMock);

        // Should only create one new option (2024)
        $this->attributeOptionManagementMock->expects($this->once())->method('add');

        $result = $this->attributeOptionManager->bulkGetOrCreateOptionIds($attributeCode, $labels);

        $this->assertEquals(['2022' => 1, '2023' => 2, '2024' => 3], $result);
    }

    /**
     * @test
     */
    public function bulkGetOrCreateOptionIdsSkipsEmptyLabels(): void
    {
        $attributeCode = 'show_year';
        $labels = ['2023', '', '  ', '2024'];

        $attributeMock = $this->createAttributeMock(100);
        $this->attributeRepositoryMock->method('get')->willReturn($attributeMock);

        $tableSourceMock = $this->createTableSourceMock([
            ['value' => 1, 'label' => '2023'],
            ['value' => 2, 'label' => '2024']
        ]);

        $this->tableFactoryMock->method('create')->willReturn($tableSourceMock);

        $result = $this->attributeOptionManager->bulkGetOrCreateOptionIds($attributeCode, $labels);

        // Should only return valid labels (empty and whitespace skipped)
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('2023', $result);
        $this->assertArrayHasKey('2024', $result);
        $this->assertArrayNotHasKey('', $result);
    }

    /**
     * @test
     */
    public function getOptionIdUsesCache(): void
    {
        $attributeCode = 'show_year';
        $label = '2023';

        $attributeMock = $this->createAttributeMock(100);
        $this->attributeRepositoryMock->method('get')->willReturn($attributeMock);

        $tableSourceMock = $this->createTableSourceMock([
            ['value' => 42, 'label' => '2023']
        ]);

        // TableFactory should only be called once due to caching
        $this->tableFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($tableSourceMock);

        // First call
        $result1 = $this->attributeOptionManager->getOptionId($attributeCode, $label);
        // Second call should use cache
        $result2 = $this->attributeOptionManager->getOptionId($attributeCode, $label);

        $this->assertEquals(42, $result1);
        $this->assertEquals(42, $result2);
    }
}
