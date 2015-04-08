<?php
/**
 * Lengow sync block payment info purchaseorder
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Block_Payment_Info_Purchaseorder extends Mage_Payment_Block_Info {
	
    protected function _construct() {
        parent::_construct();
        $this->setTemplate('lengow/sales/payment/info/purchaseorder.phtml');
    }

}
