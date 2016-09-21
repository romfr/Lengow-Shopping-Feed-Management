<?php
/**
 * Lengow sync model order
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Quote_Item extends Mage_Sales_Model_Quote_Item {

    /**
     * Specify item price (base calculation price and converted price will be refreshed too)
     *
     * @param   float $value
     * @return  Mage_Sales_Model_Quote_Item_Abstract
     */
    public function setPrice($value)
    {
        $this->setBaseCalculationPrice(null);
        //dont set converted price to 0
        //$this->setConvertedPrice(null);
        return $this->setData('price', $value);
    }
}