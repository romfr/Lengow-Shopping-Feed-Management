<?php
/**
 * Lengow export model convert parser product
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_Catalog_Product extends Mage_Catalog_Model_Product {
    
    /**
     * Config model export
     *
     * @var object
     */
    protected $_config_model = true;

    /**
     * Initialize resources
     */
    protected function _construct() {
        $this->_init('catalog/product');
        $this->_config_model = Mage::getSingleton('export/config');
    }

    public  function getShippingInfo($product_instance) {
        $data['shipping-name'] = '';
        $data['shipping-price'] = '';
        $carrier = $this->_config_model->get('data/default_shipping_method');
        if(empty($carrier))
            return $data;
        $carrierTab = explode('_',$carrier);
        list($carrierCode,$methodCode) = $carrierTab;       
        $data['shipping-name'] = ucfirst($methodCode);
        $shippingPrice = 0;     
        $countryCode = $this->_config_model->get('data/shipping_price_based_on');
        $shippingPrice = $this->_getShippingPrice($product_instance, $carrier, $countryCode);
        if(!$shippingPrice) {
            $shippingPrice = $this->_config_model->get('data/default_shipping_price');
        }
        $data['shipping-price'] = $shippingPrice;
            
        return $data;
    }
    
    protected function _getShippingPrice($product_instance, $carrierValue, $countryCode = 'FR') {
        $carrierTab = explode('_', $carrierValue);
        list($carrierCode, $methodCode) = $carrierTab;
        $shipping = Mage::getModel('shipping/shipping');
        $methodModel = $shipping->getCarrierByCode($carrierCode);
        if($methodModel) {
            $result = $methodModel->collectRates($this->_getShippingRateRequest($product_instance, $countryCode = 'FR'));
            if($result != NULL) {
                if($result->getError()) {
                    Mage::logException(new Exception($result->getError()));
                } else {
                    foreach($result->getAllRates() as $rate) {
                        return $rate->getPrice();
                    }
                }
            } else {
                return false;
            }
        }
        return false;
    }
    
    protected function _getShippingRateRequest($product_instance, $countryCode = 'FR') {
        /** @var $request Mage_Shipping_Model_Rate_Request */
        $request = Mage::getModel('shipping/rate_request');
        $storeId = $request->getStoreId();
        if (!$request->getOrig()) {
            $request->setCountryId($countryCode)
                    ->setRegionId('')
                    ->setCity('')
                    ->setPostcode('');
        }        
        $item = Mage::getModel('sales/quote_item');
        $item->setStoreId($storeId);
        $item->setOptions($this->getCustomOptions())
             ->setProduct($this);
        $request->setAllItems(array($item));
        $request->setDestCountryId($countryCode);
        $request->setDestRegionId('');
        $request->setDestRegionCode('');
        $request->setDestPostcode('');
        $request->setPackageValue($product_instance->getPrice());
        $request->setPackageValueWithDiscount($product_instance->getFinalPrice());
        $request->setPackageWeight($product_instance->getWeight());
        $request->setFreeMethodWeight(0);
        $request->setPackageQty(1);
        $request->setStoreId(Mage::app()->getStore()->getId());
        $request->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        $request->setBaseCurrency(Mage::app()->getStore()->getBaseCurrency());
        $request->setPackageCurrency(Mage::app()->getStore()->getCurrentCurrency());
        return $request;
    }
    
    public function getPrices($product_instance, $configurable_instance = null) {
        /* @var $configurable_instance Mage_Catalog_Model_Product */
        if ($configurable_instance) {
            $price = $configurable_instance->getPrice();
            $finalPrice = $configurable_instance->getFinalPrice();
            $configurablePrice = 0;
            $configurableOldPrice = 0;
            $attributes = $configurable_instance->getTypeInstance(true)->getConfigurableAttributes($configurable_instance);
            $attributes = Mage::helper('core')->decorateArray($attributes);
            if($attributes) {
                foreach($attributes as $attribute) {
                    $productAttribute   = $attribute->getProductAttribute();
                    $productAttributeId = $productAttribute->getId();
                    $attributeValue     = $product_instance->getData($productAttribute->getAttributeCode());
                    if(count($attribute->getPrices()) > 0) {
                        foreach($attribute->getPrices() as $priceChange) {
                            if (is_array($price) && array_key_exists('value_index', $price) && $price['value_index'] == $attributeValue) {
                                $configurableOldPrice += (float) ( $priceChange['is_percent'] ? ( ( (float) $priceChange['pricing_value'] ) * $price / 100 ) : $priceChange['pricing_value'] );
                                $configurablePrice += (float) ( $priceChange['is_percent'] ? ( ( (float) $priceChange['pricing_value'] ) * $finalPrice / 100 ) : $priceChange['pricing_value'] );
                            }
                        } 
                    }
                }
            }
            $configurable_instance->setConfigurablePrice($configurablePrice);
            $configurable_instance->setParentId(true);
            Mage::dispatchEvent(
                'catalog_product_type_configurable_price',
                array('product' => $configurable_instance)
            );
            $configurablePrice = $configurable_instance->getConfigurablePrice();
            $price_including_tax = Mage::helper('tax')->getPrice(
                $product_instance->setTaxPercent(null),
                $product_instance->getPrice() + $configurableOldPrice
            );
            $final_price_including_tax = Mage::helper('tax')->getPrice(
                $product_instance->setTaxPercent(null),
                $product_instance->getFinalPrice() + $configurablePrice
            );
        } else if($product_instance->getTypeId() == 'grouped') {
            $price = 0;
            $final_price = 0;
            $childs = Mage::getModel('catalog/product_type_grouped')->getChildrenIds($product_instance->getId());
            $childs = $childs[Mage_Catalog_Model_Product_Link::LINK_TYPE_GROUPED];
            foreach ($childs as $value) {
                $product = Mage::getModel('export/catalog_product')->load($value);
                $price += $product->getPrice();
                $final_price += $product->getFinalPrice();
            }
            $price_including_tax = Mage::helper('tax')->getPrice(
                $product_instance->setTaxPercent(null),
                $price,
                true
            );
            $final_price_including_tax = Mage::helper('tax')->getPrice(
                $product_instance->setTaxPercent(null),
                $final_price,
                true
            );
        } else {
            $price_including_tax = Mage::helper('tax')->getPrice(
                $product_instance->setTaxPercent(null),
                $product_instance->getPrice()
            );
            $final_price_including_tax = Mage::helper('tax')->getPrice(
                $product_instance->setTaxPercent(null),
                $product_instance->getFinalPrice()
            );
        }
        $discount_amount = Mage::helper('directory')->currencyConvert($price_including_tax, $this->getOriginalCurrency(), $this->getCurrentCurrencyCode()) - Mage::helper('directory')->currencyConvert($final_price_including_tax, $this->getOriginalCurrency(), $this->getCurrentCurrencyCode());
        $data['price-ttc'] = round(Mage::helper('directory')->currencyConvert($final_price_including_tax, $this->getOriginalCurrency(), $this->getCurrentCurrencyCode()), 2);
        $data['price-before-discount'] = round(Mage::helper('directory')->currencyConvert($price_including_tax, $this->getOriginalCurrency(), $this->getCurrentCurrencyCode()), 2);
        $data['discount-amount'] = $discount_amount > 0 ? round($discount_amount, 2) : '0';
        $data['discount-percent'] = $discount_amount > 0 ? round(($discount_amount * 100) / $price_including_tax, 0) : '0';
        $data['start-date-discount'] = $product_instance->getSpecialFromDate;
        $data['end-date-discount'] = $product_instance->getSpecialToDate;
        return $data;
    }
    
    public function getCategories($product_instance, $parent_instance, $id_store) {
        $id_root_category     = Mage::app()->getStore($id_store)->getRootCategoryId();
        if($product_instance->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE && isset($parent_instance)) {
            $categories = $parent_instance->getCategoryCollection()
                                          ->addPathsFilter('1/' . $id_root_category . '/')
                                          ->exportToArray();
        } else {
            $categories = $product_instance->getCategoryCollection()
                                           ->addPathsFilter('1/' . $id_root_category . '/')
                                           ->exportToArray();
        }
        $max_level = $this->_config_model->get('data/levelcategory');
        $current_level = 0;
        $category_buffer = false;
        foreach($categories as $category) {
            if($category['level'] > $current_level) {
                $current_level = $category['level'];
                $category_buffer = $category;
            }
            if($current_level > $max_level)
                break;
        }
        if(isset($category) && $category['path'] != '')
            $categories = explode('/', $category_buffer['path']);
        else
            $categories = array();
        $data['category'] = '';
        $data['category-url'] = '';        
        for($i = 1; $i <= $max_level; $i++) {
            $data['category-sub-'.($i)] = '';
            $data['category-url-sub-'.($i)] = '';
        }
        $i = 0;
        $ariane = array();
        foreach($categories as $cid) {
            $c = Mage::getModel('catalog/category')
                     ->setStoreId($id_store)
                     ->load($cid);
            if($c->getId() != 1) {
                // No root category
                if($i == 0) {
                    $data['category'] = $c->getName();
                    $data['category-url'] = $c->getUrl();
                    $ariane[] = $c->getName();
                } elseif($i <= $max_level) {
                    $ariane[] = $c->getName();
                    $data['category-sub-'.$i] = $c->getName();
                    $data['category-url-sub-'.$i] = $c->getUrl();
                }
                $i++;
            }
            if(method_exists($c, 'clearInstance'))
                $c->clearInstance();
        }
        $data['category-breadcrumb'] = implode(' > ', $ariane);
        unset($categories, $category, $ariane);
        return $data;
    }

    /**
     * Merge images child with images' parents.
     *
     *  @param    array $images of child's product
     *  @param    array $images of parent's product
     *  @return   array images merged
     */
    public function getImages($images, $parentimages = false) {
        if($parentimages !== false) {
            $images = array_merge($parentimages, $images);
            $_images = array();
            $_ids = array();
            foreach($images['images'] as $image) {
                if(array_key_exists('value_id', $image) && !in_array($image['value_id'], $_ids)) {
                    $_ids[] = $image['value_id'];
                    $_images[]['file'] = $image['file'];
                }
            }
            $images = $_images;
            unset($_images, $_ids, $parentimages);
        }
        $data = array();
        for($i = 1; $i < 6; $i++) {
            $data['image-url-'.$i] = '';
        }
        $c = 1;
        foreach($images as $i) {
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $i['file'];
            $data['image-url-' . $c++] = $url;
            if($i == 6)
                break;
        }
        return $data;        
    }

}
