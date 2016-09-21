<?php
/**
 * Lengow adminhtml export controller
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Adminhtml_Lengow_ExportController extends Mage_Adminhtml_Controller_Action {

	public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
        return $this;
    }

    /**
     * Product grid for AJAX request
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('lenexport/adminhtml_product_grid')->toHtml()
        );
    }

	public function massPublishAction()
    {
        $_product_ids = (array) $this->getRequest()->getParam('product');
        $_store_id = (integer) $this->getRequest()->getParam('store', Mage::app()->getStore()->getId());
        $_publish = (integer) $this->getRequest()->getParam('publish');
		$resource = Mage::getResourceModel('catalog/product');
		$_entity_type_id = $resource->getEntityType()->getId();
        try {
            //update all attribute in one query
            $product_action = Mage::getSingleton('catalog/product_action');
            if ($_store_id != 0) {
                $defaultStoreProductToUpdate = array();
                foreach ($_product_ids as $_product_id) {
                    $lengow_product_value = Mage::getResourceModel('catalog/product')->getAttributeRawValue($_product_id, 'lengow_product', 0);
                    if ($lengow_product_value === false){
                        $defaultStoreProductToUpdate[] = $_product_id;
                    }
                }
                //need to set default value if not set
                if (count($defaultStoreProductToUpdate)>0){
                    $product_action->updateAttributes($defaultStoreProductToUpdate, array('lengow_product' => 0), 0);
                }
                if ($_store_id != 0){
                    //set value for other store
                    $product_action->updateAttributes($_product_ids, array('lengow_product' => $_publish), $_store_id);
                }
            } else {
                $product_action->updateAttributes($_product_ids, array('lengow_product' => $_publish), $_store_id);
            }
            $this->_getSession()->addSuccess(
                Mage::helper('lenexport')->__('Total of %d record(s) were successfully updated', count($_product_ids))
            );
        }
        catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $e->getMessage() . Mage::helper('lenexport')->__('There was an error while updating product(s) publication'));
        }

        $this->_redirect('*/*/', array('store'=> $_store_id));
    }


	protected function _getSession()
    {
		return Mage::getSingleton('adminhtml/session');
	}

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow/export');
    }

}