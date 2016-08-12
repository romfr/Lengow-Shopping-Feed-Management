<?php

/**
 * Lengow sync model quote
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Benjamin Le Nevé <benjamin.le-neve@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Quote extends Mage_Sales_Model_Quote {

    protected $_rowTotalLengow = array();

    /**
     * Add products from API to current quote
     * 
     * @param SimpleXMLelement                $products         product list to be added
     * @param Lengow_Sync_Model_Marketplace   $marketplace
     * @param String                          $id_lengow_order  
     * @param boolean                         $priceIncludeTax  
     *
     * @return  Lengow_Sync_Model_Quote
     */
    public function addLengowProducts(SimpleXMLelement $products, Lengow_Sync_Model_Marketplace $marketplace, $id_lengow_order, $priceIncludeTax = true)
    {
        $order_lineid = '';
        $first = true;
        foreach ($products as $product_line) {
            if ($first || empty($order_lineid) || $order_lineid != (string) $product_line->order_lineid) {
                $first = false;
                $order_lineid = (string) $product_line->order_lineid;
                // check whether the product is canceled
                if (!empty($product_line->status)) {
                    if ($marketplace->getStateLengow((string) $product_line->status) == 'canceled') {
                      Mage::helper('lensync')->log('product ' . $product_line->sku . ' could not be added to cart - status: ' . $marketplace->getStateLengow((string) $product_line->status), $id_lengow_order);
                      continue;
                    }
                }   
                $product = $this->_findProduct($product_line);
                if ($product) {
                    // get unit price with tax
                    $price = (float) $product_line->price_unit;
                    // save total row Lengow for each product
                    $this->_rowTotalLengow[(string) $product->getId()] = $price * $product_line->quantity;
                    // if price not include tax -> get shipping cost without tax
                    if (!$priceIncludeTax) {
                        $basedOn = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON, $this->getStore());
                        $country_id = ($basedOn == 'shipping') ? $this->getShippingAddress()->getCountryId() : $this->getBillingAddress()->getCountryId();
                        $taxCalculator = Mage::getModel('tax/calculation');
                        $taxRequest    = new Varien_Object();
                        $taxRequest->setCountryId($country_id)
                                   ->setCustomerClassId($this->getCustomer()->getTaxClassId())
                                   ->setProductClassId($product->getTaxClassId());
                        $tax_rate = $taxCalculator->getRate($taxRequest);
                        $tax = (float) $taxCalculator->calcTaxAmount($price, $tax_rate, true);
                        $price = $price - $tax;
                    }
                    $product->setPrice($price);
                    $product->setFinalPrice($price);
                    //option "import with product's title from Lengow"
                    if (Mage::getStoreConfig('lensync/orders/title', $this->getStore()))
                        $product->setName((string) $product_line->title);
                    // add item to quote
                    $quote_item = Mage::getModel('lensync/quote_item')
                                        ->setProduct($product)
                                        ->setQty((int) $product_line->quantity)
                                        ->setConvertedPrice($price);
                    $this->addItem($quote_item);
                }
            }
        }
        return $this;
    }

    /**
     * Find product in Magento based on API data
     * 
     * @param SimpleXMLelement $lengow_product product data
     *
     * @return Mage_Catalog_Model_Product   product found to be added
     */
    protected function _findProduct(SimpleXMLelement $lengow_product)
    {
        $api_fields = array(
            'sku',
            'idLengow',
            'idMP',
            'ean',
        );
        $product_field = strtolower((string) $lengow_product->sku['field'][0]);
        $product_model = Mage::getModel('catalog/product');
        // search product foreach sku
        $i = 0;
        $found = false;
        $product = false;
        $count = count($api_fields);
        while(!$found && $i < $count) {
            // search with sku type field first
            $sku = (string) $lengow_product->{$api_fields[$i]};
            $i++;
            if (empty($sku))
                continue;
            // search by field if exists
            $attributeModel = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $product_field);
            if ($attributeModel->getAttributeId()) {
                $collection = Mage::getResourceModel('catalog/product_collection')
                                  ->setStoreId($this->getStore()->getStoreId())
                                  ->addAttributeToSelect($product_field)
                                  ->addAttributeToFilter($product_field, $sku)
                                  ->setPage(1,1)
                                  ->getData();
                if(is_array($collection) && count($collection) > 0) {
                    $product = $product_model->load($collection[0]['entity_id']);
                }
            }
            // search by id or sku
            if (!$product || !$product->getId()) {
                if (preg_match('/^[0-9]*$/',$sku)) {
                    $product = $product_model->load((integer) $sku);
                } else {
                    $sku = str_replace('\_', '_', $sku);
                    $product = $product_model->load($product_model->getIdBySku($sku));
                }
            }
            if ($product && $product->getId())
                $found = true;
        }
        if (!$found)
            throw new Exception('product ' . (string) $lengow_product->sku . ' could not be found.');
        elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
            throw new Exception('product ' . (string) $lengow_product->sku . ' is a parent product.');
        return $product;
    }
  
    /**
     * Get row Total from Lengow
     * 
     * @param string $product_id product id
     *
     * @return string
     */
    public function getRowTotalLengow($product_id)
    {
        return $this->_rowTotalLengow[$product_id];
    }
    
}
