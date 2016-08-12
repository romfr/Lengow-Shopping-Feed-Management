<?php
/**
 * Lengow grid products block
 *
 * @category    Lengow
 * @package     Lengow_Cron
 * @author      Pierre Basile <pierre.basile@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Block_Adminhtml_Cron extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct()
    {
        $this->_controller = 'adminhtml_cron';
        $this->_blockGroup = 'lensync';
        $this->_headerText = Mage::helper('lensync')->__('Item Manager');
        $this->_addButtonLabel = Mage::helper('lensync')->__('Add Item');
        parent::__construct();
    }

    /**
     * Prepare button and grid
     *
     * @return Mage_Adminhtml_Block_Catalog_Product
     */
    protected function _prepareLayout()
    {
        $this->_removeButton('add');
        $this->_addButton(
            'import',
            array(
                'label' => $this->__('Call Cron Script'),
                'onclick' => 'popWin(\'/cron.php\', \'_blank\')',
            )
        );
        $this->_addButton(
            'configure',
            array(
                'label' => $this->__('Cron Configuration'),
                'onclick' => "popWin('{$this->getUrl('adminhtml/system_config/edit', array('section' => 'system'))}#system_cron', '_blank')",
            )
        );
        $this->setChild('grid', $this->getLayout()->createBlock('lensync/adminhtml_cron_grid', 'cron.grid'));
        return parent::_prepareLayout();
    }

    /**
     * Render grid
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }
}
