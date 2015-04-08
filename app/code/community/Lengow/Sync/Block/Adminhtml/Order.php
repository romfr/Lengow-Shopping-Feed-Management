<?php
/**
 * Lengow grid products block
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Block_Adminhtml_Order extends Mage_Adminhtml_Block_Widget_Grid_Container {

	public function __construct() {
	  	parent::__construct();	     
	    $this->_controller = 'adminhtml_order';
	    $this->_blockGroup = 'sync';
	    $this->_headerText = $this->__('Lengow orders');
	}

    /**
     * Prepare button and grid
     *
     * @return Mage_Adminhtml_Block_Catalog_Product
     */
    protected function _prepareLayout() {	
		$this->_removeButton('add');
        $this->_addButton('import', array(
            'label'   => Mage::helper('catalog')->__('Manuel import'),
            'onclick' => 'setLocation(\'' . $this->getUrl('*/*/import') . '\')',
            'class'   => 'add'
        ));
        return parent::_prepareLayout();
    }
}