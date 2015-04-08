<?php
/**
 * Lengow export model system config source category level
 * Level of deep category
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_System_Config_Source_Category_Level extends Mage_Core_Model_Config_Data {

    public function toOptionArray() {
        return array(
            array('value' => 1, 
            	  'label' => 1),
            array('value' => 2, 
            	  'label' => 2),
            array('value' => 3, 
            	  'label' => 3),
            array('value' => 4, 
                  'label' => 4),
            array('value' => 5, 
                  'label' => 5),
            array('value' => 6, 
                  'label' => 6),
            array('value' => 'all', 
                  'label' => Mage::helper('adminhtml')->__('All')),
        );
    }

}