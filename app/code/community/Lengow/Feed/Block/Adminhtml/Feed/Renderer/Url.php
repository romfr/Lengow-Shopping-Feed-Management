<?php

/**
 * Lengow Feed Action Renderer
 *
 * @category    Lengow
 * @package     Lengow_Feed
 * @author      Romain Le Polh <romain@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Feed_Block_Adminhtml_Feed_Renderer_Url extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row) {
        $url = $row->getData('url');
        echo '<a class="popup-link" target="_blank" data-link="' . $url . '" href="' . $url . '">' . Mage::helper('feed')->__('View feed') . '</a>';
    }

}
