<?php

/**
 * Lengow sync model import
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com> & Benjamin Le Nevé <benjamin.le-neve@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Import extends Varien_Object {

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = null;

    /**
     * @var Mage_Customer_Model_Customer
     */
    protected $_customer = null;

    protected $_ordersIdsImported = array();

    protected $_orderIdsAlreadyImported = array();

    protected $_result;

    protected $_resultSendOrder = "";

    protected $_isUnderVersion14 = null;
    
    /**
     * Product model
     *
     * @var Mage_Catalog_Model_Product
     */
    protected $_productModel;

    protected $_connector;

    protected $_helper;

    public static $import_start = false;

    protected $_dateFrom;

    protected $_dateTo;

    protected $_config;

    protected $_idCustomer;

    protected $_idGroup;

    protected $_apiToken;

    /**
     * Constructor
     */
    public function __construct($args)
    {
        parent::__construct();
        if(Mage::app()->getStore()->getCode() != 'admin')
            Mage::app()->setCurrentStore('admin');

        if (!is_array($args))
            return;

        foreach ($args as $key => $value) {
            $this->{'_'.$key} = $value;
        }
        $this->_helper = Mage::helper('lensync/data');
        return $this;
    }

    /**
     * Execute import process
     */
    public function exec()
    {
        self::$import_start = true;
        Mage::getSingleton('core/session')->setIsFromlengow('true');
        $orders = $this->getLengowOrders();
        if(!is_object($orders) || isset($orders->error)) {
            $error = (string) $orders->error;
            if (strtolower($error) == 'no way')
                $message = $this->_helper->__('API\'s connection refused with IP %s', (string) $orders->ip);
            $error = 'Error on lengow webservice ' . (isset($message) ? ': ' . $message : json_encode($orders));
            $this->_helper->log($error);
            return array('new' => 0, 'update' => 0, 'error' => $error);
        } else {
            $count_orders = count($orders->orders->order);
            if ($count_orders === 0) {
                $this->_helper->log(
                    'No orders to import between ' . $this->_dateFrom . ' and ' . $this->_dateTo,
                    $this->force_log_output
                );
                return false;
            }
            $this->_helper->log($count_orders . ' order' . ($count_orders > 1 ? 's ' : ' ') . 'found');
        }
        return $this->importOrders($orders);
    }

    /**
     * Retrieve Lengow orders
     * 
     * @return SimpleXmlElement list of orders to be imported
     */
    protected function getLengowOrders()
    {
        $this->_connector = Mage::getSingleton('lensync/connector');
        $this->_connector->init((integer) $this->_idCustomer, $this->_apiToken);

        if ($this->_connector->error)
            return $connector;

        return $this->_connector->api(
            'commands',
            array(
                'dateFrom' => $this->_dateFrom,
                'dateTo' => $this->_dateTo,
                'id_group' => $this->_idGroup,
                'state' => 'plugin',
                )
        );
    }

    /**
      * Makes the Orders API Url and imports all orders
      *
      * @param SimpleXmlElement $orders List of orders to be imported
      *
      * @return array Number of new and update orders
      */
    protected function importOrders($orders)
    {
        $count_orders_updated = 0;
        $count_orders_added = 0;

        foreach($orders->orders->order as $key => $order_data) {
            $model_order = Mage::getModel('lensync/order');
            $model_order->setConfig($this->_config);

            $id_lengow_order = (string) $order_data->order_id;

            if($this->_config->isDebugMode())
                $id_lengow_order .= '--'.time();

            // check if order has a status
            $marketplace_status = (string) $order_data->order_status->marketplace;
            if (empty($marketplace_status)) {
                $this->_helper->log('no order\'s status', $id_lengow_order);
                continue;
            }

            // first check if not shipped by marketplace
            if ((integer) $order_data->tracking_informations->tracking_deliveringByMarketPlace == 1) {
                $this->_helper->log('delivery by marketplace (' . (string) $order_data->marketplace . ')', $id_lengow_order);
                continue;
            }

            // convert marketplace status to Lengow equivalent
            $marketplace = Mage::getModel('lensync/marketplace');
            $marketplace->set((string) $order_data->marketplace);
            $lengow_status = $marketplace->getStateLengow($marketplace_status);

            // check if order has already been imported
            $id_order = $model_order->isAlreadyImported($id_lengow_order, (integer) $order_data->idFlux);
            if ($id_order) {
                $order_imported = Mage::getModel('sales/order')->load($id_order);
                $this->_helper->log('already imported in Magento with order ID ' . $order_imported->getIncrementId(), $id_lengow_order);
                if ($model_order->updateState($order_imported, $lengow_status, $order_data))
                    $count_orders_updated++;
            } else {
                // Import only process order or shipped order and not imported with previous module
                $id_order_magento = $this->_config->isDebugMode() ? null : (string) $order_data->order_external_id;
                if ($lengow_status == 'processing' || $lengow_status == 'shipped' && !$id_order_magento) {

                    // Create or Update customer with addresses
                    $customer = Mage::getModel('lensync/customer_customer');
                    $customer->setFromNode($order_data, $this->_config);

                    // rewrite order if processing fees not included
                    if(!$this->_config->get('orders/processing_fee')) {
                        $total_wt_proc_fees = (float) $order_data->order_amount - (float) $order_data->order_processing_fee;
                        $order_data->order_amount = new SimpleXMLElement('<order_amount><![CDATA[' . ($total_wt_proc_fees) . ']]></order_amount>');
                        $order_data->order_processing_fee = new SimpleXMLElement('<order_processing_fee><![CDATA[ ]]></order_processing_fee>');
                        $this->_helper->log('rewrite amount without processing fee', $id_lengow_order);
                        unset($total_wt_proc_fees);
                    }

                    try {
                        $quote = $this->_createQuote($id_lengow_order, $order_data, $customer, $marketplace);
                    } catch (Exception $e) {
                        $this->_helper->log('create quote fail : ' . $e->getMessage(), $id_lengow_order);
                        continue;
                    }
                    try {
                        $order = $this->makeOrder($id_lengow_order, $order_data, $quote, $model_order, true);
                    } catch (Exception $e) {
                        $this->_helper->log('create order fail : ' . $e->getMessage(), $id_lengow_order);
                    }

                    if($order) {
                        // Sync to lengow
                        if(!$this->_config->isDebugMode()) {
                            $orders = $this->_connector->api('getInternalOrderId', array(
                                           'idClient'           => (integer) $this->_idCustomer,
                                           'idFlux'             => (integer) $order_data->idFlux,
                                           'Marketplace'        => (string) $order_data->marketplace,
                                           'idCommandeMP'       => $id_lengow_order,
                                           'idCommandeMage'     => $order->getId(),
                                           'statutCommandeMP'   => (string) $order_data->order_status->lengow,
                                           'statutCommandeMage' => $order->getState(),
                                           'idQuoteMage'        => $quote->getId(),
                                           'Message'            => 'Import depuis: ' . (string) $order_data->marketplace . '<br/>idOrder: '.$id_lengow_order,
                                           'type'               => 'Magento'
                            ));
                            $this->_helper->log('order successfully synchronised with Lengow webservice (Order ' . $order->getIncrementId() . ')', $id_lengow_order);
                        }
                        $count_orders_added++;
                        $this->_helper->log('order successfully imported (Order ' . $order->getIncrementId() . ')', $id_lengow_order);
                        if($lengow_status == 'shipped') {
                            $model_order->toShip($order,
                                            (string) $order_data->tracking_informations->tracking_carrier,
                                            (string) $order_data->tracking_informations->tracking_method,
                                            (string) $order_data->tracking_informations->tracking_number
                                        );
                            $this->_helper->log('update state to "shipped" (Order ' . $order->getIncrementId() . ')', $id_lengow_order);
                        }
                        unset($customer);
                        unset($quote);
                        unset($order);
                    }
                } else {
                    if($id_order_magento)
                        $this->_helper->log('already imported in Magento with order ID ' . $id_order_magento, $id_lengow_order);
                    else 
                        $this->_helper->log('order\'s status (' . $lengow_status . ') not available to import', $id_lengow_order);
                }
                unset($model_order);
            }
        }
        self::$import_start = false;
        // Clear session
        Mage::getSingleton('core/session')->clear();
        return array('new' => $count_orders_added, 'update' => $count_orders_updated);
    }

    /**
     * Create quote
     *
     * @param string                                $id_lengow_order
     * @param SimpleXMLelement                      $order_data
     * @param Lengow_Sync_Model_Customer_Customer   $customer
     * @param Lengow_Sync_Model_Marketplace         $marketplace
     *
     * @return Lengow_Sync_Model_Quote
     */
    protected function _createQuote($id_lengow_order, SimpleXMLelement $order_data, Lengow_Sync_Model_Customer_Customer $customer, Lengow_Sync_Model_Marketplace $marketplace)
    {
        $quote = Mage::getModel('lensync/quote')
                     ->setIsMultiShipping(false)
                     ->setStore($this->_config->getStore())
                     ->setIsSuperMode(true);
        
        // import customer addresses into quote
        // Set billing Address
        $customer_billing_address = Mage::getModel('customer/address')
                                        ->load($customer->getDefaultBilling());
        $billing_address = Mage::getModel('sales/quote_address')
                               ->setShouldIgnoreValidation(true)
                               ->importCustomerAddress($customer_billing_address)
                               ->setSaveInAddressBook(0);
        
        // Set shipping Address
        $customer_shipping_address = Mage::getModel('customer/address')
                                         ->load($customer->getDefaultShipping());
        $shipping_address = Mage::getModel('sales/quote_address')
                                ->setShouldIgnoreValidation(true)
                                ->importCustomerAddress($customer_shipping_address)
                                ->setSaveInAddressBook(0)
                                ->setSameAsBilling(0);
        $quote->assignCustomerWithAddressChange($customer, $billing_address, $shipping_address);

        // check if store include tax (Product and shipping cost)
        $priceIncludeTax = Mage::helper('tax')->priceIncludesTax($quote->getStore());
        $shippingIncludeTax = Mage::helper('tax')->shippingPriceIncludesTax($quote->getStore());
        
        // add product in quote
        $quote->addLengowProducts($order_data->cart->products->product, $marketplace, $id_lengow_order, $priceIncludeTax);
        
        // get shipping cost with tax
        $shipping_cost = (float) $order_data->order_processing_fee + (float) $order_data->order_shipping;

        // if shipping cost not include tax -> get shipping cost without tax
        if(!$shippingIncludeTax) {
            $basedOn = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON, $quote->getStore());
            $country_id = ($basedOn == 'shipping') ? $shipping_address->getCountryId() : $billing_address->getCountryId();
            $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $quote->getStore());
            $taxCalculator = Mage::getModel('tax/calculation');
            $taxRequest    = new Varien_Object();
            $taxRequest->setCountryId($country_id)
                       ->setCustomerClassId($customer->getTaxClassId())
                       ->setProductClassId($shippingTaxClass);
            $tax_rate = (float) $taxCalculator->getRate($taxRequest);
            $tax_shipping_cost = (float) $taxCalculator->calcTaxAmount($shipping_cost, $tax_rate, true);
            $shipping_cost = $shipping_cost - $tax_shipping_cost;
        }

        // get and update shipping rates for current order
        $rates = $quote->getShippingAddress() 
                        ->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->getShippingRatesCollection();
        $shipping_method = $this->updateRates($rates, $id_lengow_order, $shipping_cost);

        // set shipping price and shipping method for current order       
        $quote->getShippingAddress()
                ->setShippingPrice($shipping_cost)
                ->setShippingMethod($shipping_method);
        
        // collect totals
        $quote->collectTotals();

        // Re-ajuste cents for item quote
        // Conversion Tax Include > Tax Exclude > Tax Include maybe make 0.01 amount error
        if (!$priceIncludeTax) {
            if($quote->getGrandTotal() != (float) $order_data->order_amount) {
                $quote_items = $quote->getAllItems();
                foreach ($quote_items as $item) {
                    $row_total_lengow = (float) $quote->getRowTotalLengow((string) $item->getProduct()->getId());
                    if ($row_total_lengow != $item->getRowTotalInclTax()) {
                        $diff = $row_total_lengow - $item->getRowTotalInclTax();
                        $item->setPriceInclTax($item->getPriceInclTax() + ($diff / $item->getQty()));
                        $item->setBasePriceInclTax($item->getPriceInclTax());
                        $item->setPrice($item->getPrice() + ($diff / $item->getQty()));
                        $item->setOriginalPrice($item->getPrice());
                        $item->setRowTotal($item->getRowTotal() + $diff);
                        $item->setBaseRowTotal($item->getRowTotal());
                        $item->setRowTotalInclTax((float) $row_total_lengow);
                        $item->setBaseRowTotalInclTax($item->getRowTotalInclTax());
                    }
                }
            }
        }

        // set payment method lengow
        $quote->getPayment()
              ->importData(
                array(
                    'method' => 'lengow',
                    'marketplace' => (string) $order_data->marketplace . ' - ' . (string) $order_data->order_payment->payment_type,
                    )
                );

        $quote->save();
        return $quote;
    }

    /**
     * Create order
     *
     * @param string                    $id_lengow_order
     * @param SimpleXMLelement          $order_data
     * @param Lengow_Sync_Model_Quote   $quote 
     * @param Lengow_Sync_Model_Order   $model_order
     * @param boolean                   $invoice
     * 
     * @return Mage_Sales_Model_Order 
     */
    protected function makeOrder($id_lengow_order, SimpleXMLelement $order_data, Lengow_Sync_Model_Quote $quote, Mage_Sales_Model_Order $model_order, $invoice = true)
    {
        try {
            $additional_data = array('from_lengow' => true ,
                                     'marketplace_lengow' => (string) $order_data->marketplace ,
                                     'fees_lengow' => (float) $order_data->order_commission ,
                                     'order_id_lengow' => $id_lengow_order ,
                                     'feed_id_lengow' => (integer) $order_data->idFlux ,
                                     'xml_node_lengow' => Mage::helper('Core')->jsonEncode($order_data) ,
                                     'message_lengow' => (string) $order_data->order_comments ,
                                     'total_paid_lengow' => (float) $order_data->order_amount ,
                                     'carrier_lengow' => (string) $order_data->tracking_informations->tracking_carrier ,
                                     'carrier_method_lengow' => (string) $order_data->tracking_informations->tracking_method ,
                                     'carrier_tracking_lengow' => (string) $order_data->tracking_informations->tracking_number ,
                                     'carrier_id_relay_lengow' => (string) $order_data->tracking_informations->tracking_relay ,
                                     'global_currency_code' => (string) $order_data->order_currency ,
                                     'base_currency_code' => (string) $order_data->order_currency ,
                                     'store_currency_code' => (string) $order_data->order_currency ,
                                     'order_currency_code' => (string) $order_data->order_currency ,
                                    );

            $service = Mage::getModel('sales/service_quote', $quote);
            $service->setOrderData($additional_data);
            $order = false;
            if(method_exists($service, 'submitAll')) {
                $service->submitAll();
                $order = $service->getOrder();
            } else {
                $order = $service->submit();
            }
            if (!$order)
                throw new Exception('service unable to create order based on given quote');

            $order->setIsFromLengow(true);
            // modify order dates to use actual dates
            if ($this->_config->get('orders/date_import')) {
                $date_mp = (string) $order_data->order_purchase_date . ' ' . (string) $order_data->order_purchase_heure;
                $server_timezone = date_default_timezone_get();
                date_default_timezone_set(Mage::getStoreConfig('general/locale/timezone'));
                $date_UTC = gmdate('Y-m-d H:i:s', strtotime($date_mp));
                date_default_timezone_set($server_timezone);
                $order->setCreatedAt($date_UTC);
                $order->setUpdatedAt($date_UTC);
            }
            $order->save();

            // Re-ajuste cents for total and shipping cost
            // Conversion Tax Include > Tax Exclude > Tax Include maybe make 0.01 amount error  
            $priceIncludeTax = Mage::helper('tax')->priceIncludesTax($quote->getStore());
            $shippingIncludeTax = Mage::helper('tax')->shippingPriceIncludesTax($quote->getStore());   
            if (!$priceIncludeTax || !$shippingIncludeTax) {
                if($order->getGrandTotal() != (float) $order_data->order_amount) {
                    // check Grand Total
                    $diff = (((float) $order_data->order_amount) - $order->getGrandTotal());
                    $order->setGrandTotal((float) $order_data->order_amount);
                    $order->setBaseGrandTotal($order->getGrandTotal());
                    // if the difference is only on the grand total, removing the difference of shipping cost
                    if (($order->getSubtotalInclTax() + $order->getShippingInclTax()) == (float) $order_data->order_amount) {
                        $order->setShippingAmount($order->getShippingAmount() + $diff);
                        $order->setBaseShippingAmount($order->getShippingAmount());
                    } else {
                        // check Shipping Cost
                        $diff_shipping = 0;
                        $shipping_cost = (float) $order_data->order_processing_fee + (float) $order_data->order_shipping;
                        if($order->getShippingInclTax() != (float) $shipping_cost) {
                            $diff_shipping = ($shipping_cost - $order->getShippingInclTax());
                            $order->setShippingAmount($order->getShippingAmount() + $diff_shipping);
                            $order->setBaseShippingAmount($order->getShippingAmount());
                            $order->setShippingInclTax($shipping_cost);
                            $order->setBaseShippingInclTax($order->getShippingInclTax());
                        }
                        // update Subtotal without shipping cost
                        $order->setSubtotalInclTax($order->getSubtotalInclTax() + ($diff - $diff_shipping));
                        $order->setBaseSubtotalInclTax($order->getSubtotalInclTax());
                        $order->setSubtotal($order->getSubtotal() + ($diff - $diff_shipping));
                        $order->setBaseSubtotal($order->getSubtotal());
                    }
                }
                $order->save();
            }

            // generate invoice for order
            if ($invoice && $order->canInvoice()) {
                $model_order->toInvoice($order);
            }

            $carrier_name = (string) $order_data->tracking_informations->tracking_carrier;
            if ((string) $carrier_name === 'None' || $carrier_name == '')
                $carrier_name = (string) $order_data->tracking_informations->tracking_method;
            $order->setShippingDescription($order->getShippingDescription() . ' [marketplace shipping method : ' . $carrier_name . ']');

            $order->save();
        } catch (Exception $e){
            Mage::helper('lensync')->log('error create order : ' . $e->getMessage(), $id_lengow_order);
        }
        return $order;
    }

    /**
     * Update Rates with shipping cost
     *
     * @param           $rates 
     * @param string    $id_lengow_order
     * @param float     $shipping_cost
     * @param string    $shipping_method
     * @param boolean   $first                  stop recursive effect
     *
     * @return boolean
     */
    protected function updateRates($rates, $id_lengow_order, $shipping_cost, $shipping_method = null, $first = true)
    {
        if (!$shipping_method)
            $shipping_method = $this->_config->get('orders/default_shipping');
        foreach ($rates as &$rate) {
            // make sure the chosen shipping method is correct
            if ($rate->getCode() == $shipping_method) { 
                if ($rate->getPrice() != $shipping_cost) {
                    $rate->setPrice($shipping_cost);
                    $rate->setCost($shipping_cost);
                }
                return $rate->getCode();
            }
        }
        // stop recursive effect
        if (!$first)
            return 'lengow_lengow';
        // get lengow shipping method if selected shipping method is unavailable
        $this->_helper->log('the selected shipping method is unavailable for current order. Lengow shipping method assigned.', $id_lengow_order);
        return $this->updateRates($rates, $id_lengow_order, $shipping_cost, 'lengow_lengow', false);
    }
}