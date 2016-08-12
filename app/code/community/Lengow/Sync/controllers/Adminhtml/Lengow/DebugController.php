<?php
/**
 * Lengow adminhtml log controller
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Pierre Basile <pierre.basile@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Adminhtml_Lengow_DebugController extends Mage_Adminhtml_Controller_Action {

    //can access page without secret key
    public function preDispatch()
    {
        if ($this->getRequest()->getActionName() == 'index') Mage::getSingleton('adminhtml/url')->turnOffSecretKey();
        parent::preDispatch();
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
        return $this;
    }

    //call when use the grid
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody($this->getLayout()->createBlock('lensync/adminhtml_cron_grid')->toHtml());
        return $this;
    }

}