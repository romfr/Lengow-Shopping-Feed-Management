<?php
/**
 * Lengow select products block
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Block_Adminhtml_Product extends Mage_Adminhtml_Block_Widget_Container {

    /**
     * Set template
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Prepare button and grid
     *
     * @return Mage_Adminhtml_Block_Catalog_Product
     */
    protected function _prepareLayout() {
        $this->_addButton('export', array(
            'label'   => Mage::helper('export')->__('See the export feed'),
            'onclick' => 'popWin(\''.$this->getUrl('lengow/feed').'\', \'_blank\')',
            'class'   => 'add'
        ));
        $this->setChild('grid', $this->getLayout()->createBlock('export/adminhtml_product_grid', 'product.grid'));
        return parent::_prepareLayout();
    }

    /**
     * Render grid
     *
     * @return string
     */
    public function getGridHtml() {
        return $this->getChildHtml('grid');
    }

    /**
     * Check whether it is single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode() {
        if (!Mage::app()->isSingleStoreMode()) {
               return false;
        }
        return true;
    }
}
