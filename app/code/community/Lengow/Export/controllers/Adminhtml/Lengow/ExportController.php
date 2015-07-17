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

    /**
     * Checks permissions
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow/export');
    }

	public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
        return $this;
    }

    /**
     * Product grid for AJAX request
     */
    public function gridAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('export/adminhtml_product_grid')->toHtml()
        );
    }

	public function massPublishAction() {
        $_product_ids = (array) $this->getRequest()->getParam('product');
        $_store_id = (int)$this->getRequest()->getParam('store', 0);
        $_publish = (int)$this->getRequest()->getParam('publish');
		$resource = Mage::getResourceModel('catalog/product');
		$_entity_type_id = $resource->getEntityType()->getId();
        try {
            foreach ($_product_ids as $_product_id) {
                $product = new Varien_Object(array('entity_id' => $_product_id,
                    							   'id' => $_product_id,
                    							   'entity_type_id' => $_entity_type_id,
                    							   'store_id' => $_store_id,
                    							   'lengow_product' => $_publish));
                $resource->saveAttribute($product,'lengow_product');

            }
            $this->_getSession()->addSuccess(
                Mage::helper('export')->__('Total of %d record(s) were successfully updated', count($_product_ids))
            );
        }
        catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $e->getMessage() . Mage::helper('export')->__('There was an error while updating product(s) publication'));
        }

        $this->_redirect('*/*/', array('store'=> $_store_id));
    }


	protected function _getSession() {
		return Mage::getSingleton('adminhtml/session');
	}

}