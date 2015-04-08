<?php
/**
 * Lengow export model source types
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_Source_Types {

    public function toOptionArray() {
    	$attributes = Mage::getSingleton('export/convert_parser_product')->getExternalAttributes();
    	array_unshift($attributes, array('value' => 'none' ,
    									 'label' => $this->__('Sélectionnez l\'attribut à mapper')));
        return $attributes;        
    }

}
