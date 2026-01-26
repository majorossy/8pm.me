<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Ui\Component\DataProvider\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Data provider for Archive.org Product Listing grid
 *
 * Filters products to only show those imported from Archive.org
 * (where identifier attribute is not null)
 */
class Listing extends AbstractDataProvider
{
    private CollectionFactory $collectionFactory;
    private FilterBuilder $filterBuilder;
    private RequestInterface $request;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param FilterBuilder $filterBuilder
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        FilterBuilder $filterBuilder,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collectionFactory = $collectionFactory;
        $this->filterBuilder = $filterBuilder;
        $this->request = $request;
        $this->collection = $this->collectionFactory->create();
        $this->prepareCollection();
    }

    /**
     * Prepare collection with Archive.org filter
     *
     * @return void
     */
    private function prepareCollection(): void
    {
        $this->collection->addAttributeToSelect([
            'name',
            'sku',
            'status',
            'title',
            'identifier',
            'archive_collection',
            'show_year',
            'show_venue',
            'created_at'
        ]);

        // Only show products with Archive.org identifier (imported products)
        $this->collection->addAttributeToFilter('identifier', ['notnull' => true]);
        $this->collection->addAttributeToFilter('identifier', ['neq' => '']);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }

        $items = $this->getCollection()->toArray();

        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => array_values($items),
        ];
    }

    /**
     * Add field filter to collection
     *
     * @param \Magento\Framework\Api\Filter $filter
     * @return void
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter): void
    {
        $field = $filter->getField();

        // Handle EAV attributes that need special filtering
        $eavAttributes = [
            'name', 'title', 'identifier', 'archive_collection',
            'show_year', 'show_venue', 'status'
        ];

        if (in_array($field, $eavAttributes)) {
            $conditionType = $filter->getConditionType() ?: 'eq';
            $this->getCollection()->addAttributeToFilter($field, [$conditionType => $filter->getValue()]);
        } else {
            parent::addFilter($filter);
        }
    }
}
