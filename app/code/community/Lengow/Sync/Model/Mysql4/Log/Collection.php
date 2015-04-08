<?php
/**
 * Lengow sync model mysql4 log collection
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Mysql4_Log_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

	protected function _construct()	{
		$this->_init('sync/log');
	}
}