<?php
/**
 * Lengow Backend Model for Config Api key
 * @category   Lengow
 * @package    Lengow_Export
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class Lengow_Export_Model_System_Config_Backend_Apikey extends Mage_Core_Model_Config_Data {

	protected function _beforeSave() {
		parent::_beforeSave();		
		if((boolean)$this->getFieldsetDataValue('enabled') && $this->getValue() == '')
			Mage::throwException(Mage::helper('sync')->__('API Key (Token) is empty'));		
		if($this->isValueChanged()) {
			/* @var $service Lengow_Export_Model_ManageOrders_Service */
			$service = Mage::getSingleton('Lengow_Export/manageorders_service');			
			if((boolean)$this->getFieldsetDataValue('enabled') && !$service->checkApiKey($this->getValue()))
				Mage::throwException(Mage::helper('sync')->__('API key (Token) not valid'));
			
		}
	}
	
}