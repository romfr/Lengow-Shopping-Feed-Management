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
 * Lengow_Tracker_Block_Adminhtml_System_Config_Check_Point
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2014 Lengow SAS
 */

class Lengow_Tracker_Block_Adminhtml_System_Config_Check_Point extends Mage_Adminhtml_Block_Template {

	protected $_element;

	protected $_helper;

    public function __construct() {
    	$this->setTemplate('lengow/check/point.phtml');
    	$this->_helper = Mage::helper('lentracker/check');
    	parent::_construct();
    }

    public function getVersion() {
    	return $this->_helper->getVersion();
    }

    public function isLastVersion() {
    	return $this->_helper->checkPluginVersion($this->_helper->getVersion());
    }

    public function getLastVersion() {
    	return $this->_helper->getLastVersion();
    }

    public function getRealIP() {
    	return $this->_helper->getRealIP();
    }

}