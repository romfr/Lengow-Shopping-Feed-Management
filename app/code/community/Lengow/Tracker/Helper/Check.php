<?php
/**
 * Lengow sync helper data
 *
 * @category    Lengow
 * @package     Lengow_Tracker
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2014 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Tracker_Helper_Check extends Mage_Core_Helper_Abstract {

    const PLUGIN_NAME = 'magento';

    const URI_TAG_CAPSULE = 'plugins.xml';

    const LENGOW_PLUGINS_VERSION = 'http://kml.lengow.com/plugins.xml';

    protected $_dom;

    public function getVersion() {
        $this->updatePluginsVersion();
        return (string) Mage::getConfig()->getNode()->modules->Lengow_Tracker->version;
    }

    public function getLastVersion() {
        $this->_loadDom();
        // Compare version
        $object = $this->_dom->xpath('/plugins/plugin[@name=\'' . self::PLUGIN_NAME . '\']');
        if(!empty($object)) 
            return $object[0]->version;
        else
            return 'NULL';
    }


    /**
     * Check and update xml of plugins version
     *
     * @return boolean
     */
    public static function updatePluginsVersion() {
        $mp_update = Mage::getModel('tracker/config')->get('hidden/last_synchro');
        if (!$mp_update || !$mp_update == '0000-00-00' ||$mp_update != date('Y-m-d')) {
            $sep = DS;
            if ($xml = fopen(self::LENGOW_PLUGINS_VERSION, 'r')) {
                $handle = fopen(Mage::getModuleDir('etc', 'Lengow_Tracker') . DS . self::URI_TAG_CAPSULE . '', 'w');
                stream_copy_to_stream($xml, $handle);
                fclose($handle);
                Mage::getModel('core/config')->saveConfig('tracker/hidden/last_synchro', date('Y-m-d'));
            }
        }
    }

    /**
     * Check module version
     *
     * @return boolean true if up to date, false if old version currently installed
     */
    public function checkPluginVersion($current_version = null) {
        if($current_version == null)
            return false;
        $this->_loadDom();
        // Compare version
        $object = $this->_dom->xpath('/plugins/plugin[@name=\'' . self::PLUGIN_NAME . '\']');
        if(!empty($object)) {
            $plugin = $object[0];
            if(version_compare($current_version, $plugin->version, '<')) {
                return false;
            } else {
                return true;
            }
        }
        return true;
    }

    public function getRealIP() {
        return $_SERVER['SERVER_ADDR'];
    }

    private function _loadDom() {
        if(!$this->_dom)
            $this->_dom = simplexml_load_file(Mage::getModuleDir('etc', 'Lengow_Tracker') . DS . self::URI_TAG_CAPSULE );
    }

}