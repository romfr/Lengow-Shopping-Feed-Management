<?php
/**
 * Lengow sync model customer
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Customer_Customer extends Mage_Customer_Model_Customer {
    
    /**
     * Convert xml node to customer model
     *
     * @param   $xml_node SimpleXMLElement
     */
    public function setFromNode(SimpleXMLElement $xml_node, $id_store) {
        $id_store = Mage::getModel('core/store')->load($id_store)->getWebsiteId();
        $array = Mage::helper('sync')->xmlToAssoc($xml_node);
        if(empty($array['billing_address']['billing_email']))
            $array['billing_address']['billing_email'] = 'no-mail-' . $array['order_id'] . '@' . $array['marketplace'] . '.com';
        if(empty($array['billing_address']['billing_firstname']))
            $array['billing_address']['billing_firstname'] = '__';
        if(empty($array['delivery_address']['delivery_firstname']))
            $array['delivery_address']['delivery_firstname'] = '__';
        $this->setWebsiteId($id_store)
             ->loadByEmail($array['billing_address']['billing_email']);    
        if(!$this->getId()) {
            $this->setImportMode(true);        
            $this->setWebsiteId($id_store);
            $this->setConfirmation(null);
            $this->setForceConfirmed(true);
            $this->setPasswordHash($this->hashPassword($this->generatePassword(8)));
            $this->setFromLengow(1);
        }
        // Billing address
        $billing_address = $this->convertAddress($array['billing_address']);            
        $this->addAddress($billing_address);
        // Shipping address
        $shipping_address = $this->convertAddress($array['delivery_address'], 'shipping');
        $this->addAddress($shipping_address);
        Mage::helper('core')->copyFieldset('lengow_convert_billing_address', 'to_customer', $array['billing_address'], $this);
        $this->save();
        return $this;
    }
    
    /**
     * Convert xml node to customer address model
     *
     * @param   array $data
     * @return  Mage_Customer_Model_Address
     */
    public function convertAddress(array $data, $type = 'billing') {
        $address = Mage::getModel('customer/address');
        $address->setId(null);
        $address->setIsDefaultBilling(true);
        $address->setIsDefaultShipping(false);
        if($type == 'shipping') {
            $address->setIsDefaultBilling(false);
            $address->setIsDefaultShipping(true);
        }
        Mage::helper('core')->copyFieldset('lengow_convert_' . $type . '_address', 'to_' . $type . '_address', $data, $address);     
        if($type == 'shipping')
            $type = 'delivery';
        $address_1 = $data[$type . '_address'];
        $address_2 = $data[$type . '_address_2'];
        // Fix address 1
        if(empty($address_1) && !empty($address_2)) {
            $address_1 = $address_2;
            $address_2 = null;
        }
        // Fix address 2 
        if(!empty($address_2))
            $address_1 = $address_1 . "\n" . $address_2;
        $address_3 = $data[$type . '_address_complement'];
        if(!empty($address_3))
            $address_1 = $address_1 . "\n" . $address_3;
        $address->setStreet($address_1);
        $tel_1 = $data[$type . '_phone_office'];
        $tel_2 = $data[$type . '_phone_mobile'];
        // Fix tel
        if(!empty($tel_1))
            $address->setFax($tel_1);
        else if(!empty($tel_2))
            $address->setFax($tel_2);
        $codeRegion = substr(str_pad($address->getPostcode(), 5, '0', STR_PAD_LEFT), 0, 2);
        $id_region = Mage::getModel('directory/region')->getCollection()
                                                       ->addRegionCodeFilter($codeRegion)
                                                       ->addCountryFilter($address->getCountry())
                                                       ->getFirstItem()
                                                       ->getId();   
        $address->setRegionId($id_region);
        return $address;
    }
}