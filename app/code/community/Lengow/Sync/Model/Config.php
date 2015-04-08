<?php
/**
 * Lengow sync model config
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Config extends Varien_Object {
    
    /**
     * Config key "Debug mode"
     */
    const DEBUG_MODE = 'orders/debug';
    
    const MP_CONF_LENGOW = 'http://kml.lengow.com/mp.xml';
    
    protected $_id_store;

    protected $_file;

    public static $ADDRESS_ATTRIBUTES = array(
                            'prefix' => 'na',
                            'firstname' => 'firstname',
                            'middlename' => 'na',
                            'lastname' => 'lastname',
                            'suffix' => 'na',
                            'company' => 'society',
                            'street' => array('address', 'address_2', 'address_complement'),
                            'city' => 'city',
                            'country_id' => 'country',
                            'region' => 'na',
                            'region_id' => 'na',
                            'postcode' => 'zipcode',
                            'telephone' => 'phone_home',
                            'fax' => 'phone_office',
                            'vat_id' => 'na',
                    );

    public function setStore($id_store) {
        $this->_id_store = $id_store;
        return $this;
    }

    public function getConfig($key) {
        return Mage::getStoreConfig($key, $this->_id_store);
    }

    public function get($key) {
        return Mage::getStoreConfig('sync/' . $key, $this->_id_store);
    }

    /**
     * Map Magento address attribute codes with Neteven ones
     *
     * @param string $attributeCode
     * @return mixed
     */
    public function getMappedAddressAttributeCode($attribute_code) {
        return self::$ADDRESS_ATTRIBUTES[$attribute_code];
    }

    public function getOrderState($lengow) {
        switch ($lengow) {
            case 'new':
                return Mage_Sales_Model_Order::STATE_NEW;
                break;
            case 'processing':
                return Mage_Sales_Model_Order::STATE_PROCESSING;
                break;
            case 'shipped':
                return Mage_Sales_Model_Order::STATE_COMPLETE;
                break;
            case 'canceled':
                return Mage_Sales_Model_Order::STATE_CANCELED;
                break;  
        }
    }

    /**
     * Is debug mode
     *
     * @return boolean
     */
    public function isDebugMode() {
        return $this->get(self::DEBUG_MODE);
    }

    /**
     * Check and update xml of marketplace's configuration.
     *
     * @return boolean.
     */
    public function updateMarketPlaceConfiguration() {
        if ($xml = fopen(self::MP_CONF_LENGOW, 'r')) {
            $markeplace = Mage::getModel('sync/marketplace');
            $handle = fopen(Mage::getModuleDir('etc', 'Lengow_Sync') . DS . $markeplace::$XML_MARKETPLACES . '', 'w');
            stream_copy_to_stream($xml, $handle);
            fclose($handle);
            Mage::getModel('core/config')->saveConfig('sync/hidden/last_synchro', date('Y-m-d'));
        }
    }

    /**
     * Check if import can be started
     *
     * @return boolean.
     */
    public function importCanStart() {
        $timestamp = $this->readFlagFile();
        if($timestamp !== false) {
            $this->createFlagFile();
            return true;
        }
        if((time() - (integer) $timestamp) < (60*25)) {
            return false;
        } else {
            $this->createFlagFile();
            return true;
        }
    }

    /**
     * Create file flag.
     *
     * @return boolean.
     */
    public  function readFlagFile() {
        $file_path = Mage::getBaseDir('media') . DS . 'lengow' . DS;
        if(is_file($file_path . 'import.flag')) {
            $this->_file = Mage::getModel('sync/file');
            $this->_file->checkAndCreateFolder($file_path);
            $this->_file->cd($file_path);
            $this->_file->streamOpen('import.flag', 'r+');
            $this->_file->streamLock();
            return $this->_file->streamRead(4096);
        } else {
            return false;
        }
    }

    /**
     * Create file flag.
     *
     * @return boolean.
     */
    public  function createFlagFile($clean = false) {
        if($clean)
            $_time = 0;
        else
            $_time = time();
        $file_path = Mage::getBaseDir('media') . DS . 'lengow' . DS;
        $this->_file->streamUnlock();
        $this->_file->streamClose();
        $this->_file->streamOpen('import.flag', 'w+');
        $this->_file->streamLock();
        $this->_file->streamWrite($_time);
        Mage::helper('sync/data')->log('Write on file log : ' . $_time);
    }

    /**
     * Clean file flag.
     *
     * @return boolean.
     */
    public  function cleanFlagFile() {
        $file_path = Mage::getBaseDir('media') . DS . 'lengow' . DS;
        if(is_file($file_path . 'import.flag'))
            $this->createFlagFile(true);
            $this->_file->streamUnlock();
            $this->_file->streamClose();
    }
    
}