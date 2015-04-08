<?php
/**
 * Lengow export model config
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_Config extends Varien_Object {

	
	/**
	 * Config key "Enable manage orders"
	 */
	const ENABLED = 'active';
	
	/**
	 * Config key "Filter by attribute"
	 */
	const EXPORT_ONLY_SELECTED = 'global/export_only_selected';
	
	/**
	* Config key "Export soldout"
	*/
	const EXPORT_SOLDOUT = 'export_soldout';

	/**
	* Config key "Export count images"
	*/
	const COUNT_IMAGES = 'data/count_images';
	
	/**
	 * Config key "Filter by attribute"
	 */
	const LIMIT_PRODUCT = 'limit_product';
	
	/**
	 * Config key "Attributes kow"
	 */
	const ATTRIBUTES_KNOW = 'attributes_know';
	
	/**
	 * Config key "Attributes unkow"
	 */
	const ATTRIBUTES_UNKNOW = 'attributes_unknow';

	/**
	 * Config key "Auto export product"
	 */
	const AUTOEXPORT_NEWPRODUCT = 'autoexport_newproduct';
	
	/*
	 * @var array $_attributesKnow
	 */
	protected $_attributesKnow = null;
	
	/**
	 * @var array $_attributesUnKnow
	 */
	protected $_attributesUnKnow = null;
	
	protected $_attributesSelected = null;

	protected $_id_store;

	public function setStore($id_store) {
		$this->_id_store = $id_store;
	}

	public function get($key) {
        return Mage::getStoreConfig('export/' . $key, $this->_id_store);
	}
	
	/**
     *  Return config var
     *
     *  @param    string $key Var path key
     *  @param    int $id_store Store View Id
     *  @return	  mixed
     */
	public function getConfigData($key, $group = 'global', $id_store = null) {
		if (!$this->hasData($key)) {
            $value = Mage::getStoreConfig('export/' . $group . '/' . $key, $id_store);
            $this->setData($key, $value);
        }
        return $this->getData($key);
	}
	
	/**
     *  Return config var
     *
     *  @param    string $key Var path key
     *  @param    int $id_store Store View Id
     *  @return	  mixed
     */
	public function getConfigFlag($key, $group = 'global', $id_store = null) {
		if (!$this->hasData($key)) {
            $value = Mage::getStoreConfigFlag('export/' . $group . '/' . $key, $id_store);
            $this->setData($key, $value);
        }
        return $this->getData($key);
	}
	
	
	public function getAny($group, $key, $id_store = null) {
        return Mage::getStoreConfig('export/' . $group . '/' . $key, $id_store);
	}

	/**
	 * Retrieve if export is active
	 * 
	 * @return boolean
	 */
	public function isEnabled()	{
		return $this->getConfigFlag(self::ENABLED);
	}
	
	/**
	 * Retrieve if export is active
	 * 
	 * @return boolean
	 */
	public function onlySelectedProducts()	{
		return $this->get(self::EXPORT_ONLY_SELECTED);
	}
	
	/**
	* Retrieve if export sold out products
	*
	* @return boolean
	*/
	public function isExportSoldout() {
		return $this->getConfigFlag(self::EXPORT_SOLDOUT);
	}
	
	/**
	 * Retrieve limit of product in query
	 * 
	 * @return int
	 */
	public function getLimitProduct() {
		return (int) $this->getConfigData(self::LIMIT_PRODUCT);
	}
	
	/**
	 * Retrieve limit of product in query
	 * 
	 * @return int
	 */
	public function getCountExportImages() {
		return (int) $this->getConfigData(self::COUNT_IMAGES);
	}

	/**
	 * Auto export new product
	 * 
	 * @return int
	 */
	public function isAutoExportProduct() {
		return (int) $this->getConfigData(self::AUTOEXPORT_NEWPRODUCT);
	}
	
	/**
	 * Return Attributes Unknowed in array with key=>value
	 * key = node adn value = inner text
	 * @param int $id_store
	 * @return array 
	 */
	public function getMappgingAttributesUnKnow($id_store = null) {
		//if(is_null($this->_attributesUnKnow))
		if($this->_attributesUnKnow === null || !isset($this->_attributesUnKnow) || empty($this->_attributesUnKnow)) {
			$this->_attributesUnKnow = Mage::getStoreConfig('export/attributes_unknow', $id_store);
		}
		
		return $this->_attributesUnKnow;
	}
	
	public function getSelectedAttributes($id_store = null) {

		$tab = array();
		$this->_attributesSelected = array(); 
		if($this->_attributesSelected === null || !isset($this->_attributesSelected) || empty($this->_attributesSelected)) {
			$val = Mage::getStoreConfig('export/attributelist/attributes', $id_store);
			if(!empty($val)) {
				$tab = explode(',',$val);
				$this->_attributesSelected = array_flip($tab);
			}
		}
		if(!empty($tab)) {
			foreach($this->_attributesSelected as $key => $value) {
				$this->_attributesSelected[$key] = $key;
			}
		}
		return $this->_attributesSelected;
	}
	
	/**
	 * Return ALL Attributes Knowed and Unknowed in array with key=>value
	 * key = node adn value = inner text
	 * @return array 
	 * @param int $id_store
	 */
	public function getMappingAllAttributes($id_store = null) {
        return $this->getSelectedAttributes($id_store);
	}
	

}