<?php
/**
 * Lengow export model system config source types
 * Types of product to export
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_System_Config_Source_Types extends Mage_Core_Model_Config_Data {

    public function toOptionArray()  {
        return array(
            array('value' => 'configurable', 
            	  'label' => Mage::helper('adminhtml')->__('Configurable')),
            array('value' => 'simple', 
            	  'label' => Mage::helper('adminhtml')->__('Simple')),
            array('value' => 'bundle', 
            	  'label' => Mage::helper('adminhtml')->__('Bundle')),
            array('value' => 'grouped', 
                  'label' => Mage::helper('adminhtml')->__('Grouped')),
            array('value' => 'virtual', 
                  'label' => Mage::helper('adminhtml')->__('Virtual')),
        );
    }
    
    public function toSelectArray() {
        $select = array();
        foreach($this->toOptionArray() as $option) {
            $select[$option['value']] = $option['label'];
        }
        return $select;
    }

}