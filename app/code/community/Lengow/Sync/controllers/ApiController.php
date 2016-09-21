<?php
/**
 * Lengow export controller
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_ApiController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        echo 'Please specify an action';
    }

    public function checkAction() {
        $_helper_export = Mage::helper('lenexport/security');
        $_helper_api = Mage::helper('lensync/api');
        if($_helper_export->checkIp()) {
            $return = array('magento_version' => Mage::getVersion(),
                            'lengow_version' => $_helper_api->getVersion());
            echo Mage::helper('core')->jsonEncode($return);
        } else {
            echo 'Unauthorised ip : ' . $_SERVER['REMOTE_ADDR'];
        }
    } 

}
