<?php

/**
 * Copyright 2013 Lengow.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */


/**
 * Lengow_Tracker_Block_Adminhtml_System_Config_Check
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2014 Lengow SAS
 */

class Lengow_Tracker_Block_Adminhtml_System_Config_Check extends Mage_Adminhtml_Block_Template implements Varien_Data_Form_Element_Renderer_Interface {
	    
	protected $_element;

    protected function _construct() {
        $this->setTemplate('widget/form/renderer/fieldset.phtml');
    }

    public function getElement() {
        return $this->_element;
    }
	/**
	 * Generate html for button
	 * 
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string $html
	 * @see Mage_Adminhtml_Block_System_Config_Form_Field::_getElementHtml()
	 */
	public function render(Varien_Data_Form_Element_Abstract $element) {
		$html = $this->getLayout()->createBlock('tracker/adminhtml_system_config_check_point', 'lengow_checkpoint')
								  ->toHtml();
		$element->setHtmlContent($html);
		$this->_element = $element;
		return $this->toHtml();
	}
}