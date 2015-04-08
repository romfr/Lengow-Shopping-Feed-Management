<?php
class Lengow_Order_Block_Manageorders_Adminhtml_Sync extends Mage_Adminhtml_Block_Template {
    
	public function getSyncOrdersUrl() {
    	return $this->getUrl('*/*/SyncOrders');
    }
    
}
