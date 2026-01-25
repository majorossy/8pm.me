<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

/**
 * Artist Mappings Frontend Model
 *
 * Provides a dynamic grid for configuring artist to collection mappings
 */
class ArtistMappings extends AbstractFieldArray
{
    /**
     * @inheritDoc
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('artist_name', [
            'label' => __('Artist Name'),
            'style' => 'width: 200px',
            'class' => 'required-entry'
        ]);

        $this->addColumn('collection_id', [
            'label' => __('Collection ID'),
            'style' => 'width: 200px',
            'class' => 'required-entry',
            'comment' => __('Archive.org collection identifier')
        ]);

        $this->addColumn('category_id', [
            'label' => __('Category ID'),
            'style' => 'width: 100px',
            'comment' => __('Magento category ID (optional)')
        ]);

        $this->_addAfter = true;
        $this->_addButtonLabel = __('Add Artist Mapping');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];
        $row->setData('option_extra_attrs', $options);
    }
}
