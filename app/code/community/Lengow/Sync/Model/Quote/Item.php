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

    public function initPrice($value) {
        $store = $this->getQuote()->getStore();
        if (!Mage::helper('tax')->priceIncludesTax($store)) {
            // DEEE
            $_deee_amount = 0;
            if (!Mage::helper('tax')->priceIncludesTax($store)) {
                $_helper_weee = Mage::helper('weee');
                $_deee_amount = $_helper_weee->getAmount($this->getProduct());
            }
            $bAddress = $this->getQuote()->getBillingAddress();
            $sAddress = $this->getQuote()->getShippingAddress();
            $address = $this->getAddress();
            if ($address) {
                switch ($address->getAddressType()) {
                    case Mage_Sales_Model_Quote_Address::TYPE_BILLING:
                        $bAddress = $address;
                        break;
                    case Mage_Sales_Model_Quote_Address::TYPE_SHIPPING:
                        $sAddress = $address;
                        break;
                }
            }

            if ($this->getProduct()->getIsVirtual()) {
                $sAddress = $bAddress;
            }

            $priceExcludingTax = Mage::helper('tax')->getPrice(
                $this->getProduct()->setTaxPercent(null),
                $value,
                false,
                $sAddress,
                $bAddress,
                $this->getQuote()->getCustomerTaxClassId(),
                $store,
                true
            ) - $_deee_amount;
            $this->setCustomPrice($priceExcludingTax);
            $this->setOriginalCustomPrice($priceExcludingTax);
            $this->setOriginalPrice($priceExcludingTax);

            $priceIncludingTax = Mage::helper('tax')->getPrice(
                $this->getProduct()->setTaxPercent(null),
                $value,
                true,
                $sAddress,
                $bAddress,
                $this->getQuote()->getCustomerTaxClassId(),
                $store,
                true
            );
            $qty = $this->getQty();
            if ($this->getParentItem()) {
                $qty = $qty*$this->getParentItem()->getQty();
            }

            //if (Mage::helper('tax')->displayCartPriceInclTax($store)) {
                $rowTotal = $value * $qty;
                $rowTotalExcTax = Mage::helper('tax')->getPrice(
                    $this->getProduct()->setTaxPercent(null),
                    $rowTotal,
                    false,
                    $sAddress,
                    $bAddress,
                    $this->getQuote()->getCustomerTaxClassId(),
                    $store,
                    true
                ) - ($_deee_amount * $qty);
                $rowTotalIncTax = Mage::helper('tax')->getPrice(
                    $this->getProduct()->setTaxPercent(null),
                    $rowTotal,
                    true,
                    $sAddress,
                    $bAddress,
                    $this->getQuote()->getCustomerTaxClassId(),
                    $store,
                    true
                );
                $totalBaseTax = $rowTotalIncTax-$rowTotalExcTax;
                $this->setBaseRowTotal($rowTotal);
                $this->setBaseRowTotalInclTax($rowTotal);
            //}  else {
                $taxAmount = $priceIncludingTax - $priceExcludingTax ;
                $this->setTaxPercent($this->getProduct()->getTaxPercent());
                $totalBaseTax = $taxAmount*$qty;
            //}

            $totalTax = $this->getStore()->convertPrice($totalBaseTax);
            $this->setTaxBeforeDiscount($totalTax);
            $this->setBaseTaxBeforeDiscount($totalBaseTax);

            $this->setTaxAmount($totalTax);
            $this->setBaseTaxAmount($totalBaseTax);
            $this->setBaseOriginalPrice($priceExcludingTax);
            $this->setTaxAmount($totalTax);
            $this->setPrice($priceExcludingTax);
            $this->setPriceInclTax($priceIncludingTax);
            $this->setBasePriceInclTax($priceIncludingTax);
            return $this;
        } else {
            return $this;
        }
    }
    
}