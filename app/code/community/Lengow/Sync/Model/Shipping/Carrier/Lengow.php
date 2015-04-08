<?php
/**
 * Lengow sync model shipping carrier lengow
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Shipping_Carrier_Lengow extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

    protected $_code = 'lengow';
    protected $_isFixed = true;

    /**
     * FreeShipping Rates Collector
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
        if (!$this->isActive()) {
            return false;
        }
        $result = Mage::getModel('shipping/rate_result');
        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier('lengow');
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod('lengow');
        $method->setMethodTitle($this->getConfigData('name'));
        $method->setPrice($this->getSession()->getShippingPrice());
        $method->setCost($this->getSession()->getShippingPrice());  
        $result->append($method);  
        return $result;
    }
    
    /**
    * Processing additional validation to check is carrier applicable.
    *
    * @param Mage_Shipping_Model_Rate_Request $request
    * @return Mage_Shipping_Model_Carrier_Abstract|Mage_Shipping_Model_Rate_Result_Error|boolean
    */
    public function proccessAdditionalValidation(Mage_Shipping_Model_Rate_Request $request) {
        if(Mage::getVersion() == '1.4.1.0')
           return $this->isActive();         
        return parent::proccessAdditionalValidation($request);
    }
    
    public function getSession() {
        return Mage::getSingleton('checkout/session');
    }
    
    public function isActive() {
        if($this->getSession()->getIsFromlengow())
            return true;        
        return false;
    }

    public function getAllowedMethods() {
        return array('lengow' => $this->getConfigData('name'));
    }

}
