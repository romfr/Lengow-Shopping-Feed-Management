<?php
/**
 * Lengow sync model config
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com> & Benjamin Le Nevé <benjamin.le-neve@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Config extends Varien_Object {

    /**
     * Config key "Debug mode"
     */
    const DEBUG_MODE = 'performances/debug';

    const MP_CONF_LENGOW = 'http://kml.lengow.com/mp.xml';

    protected $store;

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

    /**
     * Constructor
     */
    public function __construct($args = null)
    {
        parent::__construct();
        if (isset($args['store']))
            $this->setStore($args['store']);
    }

    /**
     * Set store
     *
     * @param $store
     * 
     * @return Lengow_Sync_Model_Config 
     */
    public function setStore($store)
    {
        $this->store = $store;
        return $this;
    }

    /**
     * Get store from config
     *
     * @return 
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Get data from config
     *
     * @param string $key
     * 
     * @return mixed
     */
    public function get($key)
    {
        if (is_null($this->store))
            $id_store = null;
        else
            $id_store = $this->store->getId();
        return Mage::getStoreConfig('lensync/' . $key, $id_store);
    }

    /**
     * Map Magento address attribute codes with Neteven ones
     *
     * @param string $attribute_code
     * 
     * @return mixed
     */
    public function getMappedAddressAttributeCode($attribute_code)
    {
        return self::$ADDRESS_ATTRIBUTES[$attribute_code];
    }

    /**
     * Is debug mode
     *
     * @return boolean
     */
    public function isDebugMode()
    {
        return $this->get(self::DEBUG_MODE)  == 1 ? true : false;
    }

    /**
     * Check and update xml of marketplace's configuration.
     *
     * @return boolean.
     */
    public function updateMarketPlaceConfiguration()
    {
        if ($xml = fopen(self::MP_CONF_LENGOW, 'r')) {
            $markeplace = Mage::getModel('lensync/marketplace');
            $handle = fopen(Mage::getModuleDir('etc', 'Lengow_Sync') . DS . $markeplace::$XML_MARKETPLACES . '', 'w');
            stream_copy_to_stream($xml, $handle);
            fclose($handle);
            Mage::getModel('core/config')->saveConfig('lensync/hidden/last_synchro', date('Y-m-d'));
        }
    }

    /**
     * Check if import can be started
     *
     * @return boolean.
     */
    public function importCanStart()
    {
        if (is_null($this->_file))
            $this->getFlagFile();
        if (!$this->addFlagToFile()) {
            $timestamp = $this->readFlagFile();
            if ($timestamp !== false && (time() - (integer) $timestamp) > (60*25)) {
                $this->addFlagToFile();
                return true;
            }
            return false;
        }
        return true;
    }
    
    /**
     * Finish import
     *
     */
    public function importSetEnd()
    {
        $this->getFlagFile();
        $this->_file->streamUnlock();
        $this->addFlagToFile(true);
        $this->_file->streamClose();
    }

    /**
     * Add flag to file
     *
     * @param boolean $reset
     *
     * @return boolean
     */
    public function addFlagToFile($reset = false)
    {

        if ($this->_file->streamLock()) {
            if ($reset) {
                if ($this->_file->streamErase())
                    $this->_file->streamWrite('0');
            }
            else {
                $this->_file->streamWrite(time());
            }
            return true;
        }
        else
            return false;
    }

    /**
     * Read file flag
     *
     * @return boolean
     */
    public  function readFlagFile()
    {
        $file_path = Mage::getBaseDir('media') . DS . 'lengow' . DS;
        if(is_file($file_path . 'import.flag')) {
            $this->_file->cd($file_path);
            $this->_file->streamLock();
            return $this->_file->streamRead(4096);
        } else {
            return false;
        }
    }

    /**
     * Create file flag
     *
     * @return boolean
     */
    public  function getFlagFile()
    {
        $this->_file = new Lengow_Sync_Model_File();
        $file_path = Mage::getBaseDir('media') . DS . 'lengow' . DS;
        $this->_file->checkAndCreateFolder($file_path);
        $this->_file->cd($file_path);
        $this->_file->streamOpen('import.flag', 'w+');
        Mage::helper('lensync/data')->log('Write on file log');
    }
}