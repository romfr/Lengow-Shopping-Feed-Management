<?php

/**
 * Lengow grid products block
 *
 * @category    Lengow
 * @package     Lengow_Feed
 * @author      Romain Le Polh <romain@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Feed_Block_Adminhtml_Feed extends Mage_Adminhtml_Block_Widget_Container {

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
        $this->_addButton('migrate', array(
            'label'   => Mage::helper('feed')->__('Migrate all selected feeds'),
            'onclick' => 'form_grid_feed.submit()',
            'class'   => 'save'
        ));
        $this->setChild('grid', $this->getLayout()->createBlock('feed/adminhtml_feed_grid', 'feed.grid'));
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
