<?php
/**
 * Lengow logs
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Block_Adminhtml_Log extends Mage_Adminhtml_Block_Widget_Grid_Container {

	public function __construct() {
	  	parent::__construct();	     
	    $this->_controller = 'adminhtml_log';
	    $this->_blockGroup = 'sync';
	    $this->_headerText = $this->__('Lengow logs');	
		$this->_removeButton('add');
		$this->_addButton('deleteAll', array(
											'label'   => Mage::helper('sync')->__('Flush logs'),
           									'onclick' => 'setLocation(\'' . $this->getUrl('*/*/delete') . '\')',
								            'class'   => 'delete'
											));
	}
}