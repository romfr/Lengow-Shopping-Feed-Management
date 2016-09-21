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
        $array = array();
        for($i = 1; $i <= 10; $i++) {
            $array[] = array('value' => $i, 'label' => $i);
        }
        return $array;
    }

}