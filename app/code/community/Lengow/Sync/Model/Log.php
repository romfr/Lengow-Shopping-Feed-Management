<?php
/**
 * Lengow sync model log
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Log extends Mage_Core_Model_Abstract {

	protected function _construct() {
		$this->_init('sync/log');
	}
	
	/**
	 * Save message event
	 * @param $message string
	 * @param $id_order int
	 */
	public function log($message, $id_order = null) {
		$order_message = '';
		if(!is_null($id_order)) {
			$order_message = Mage::helper('sync')->__('ID Order') . ' Lengow #' . $id_order . ' ';
		}	
		$message = $order_message . $message;		
		$this->setMessage($message);		
		return $this->save();
	}
	
}