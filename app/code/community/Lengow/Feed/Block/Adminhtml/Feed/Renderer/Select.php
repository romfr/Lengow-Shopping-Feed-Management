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
class Lengow_Feed_Block_Adminhtml_Feed_Renderer_Select
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $column = $this->getColumn();
        $index = $column->getIndex();
        $options = '';
        
        $multiple = '';
        $additional_name = '';
        
        if($column->getMultiple() == true) {
            $multiple = 'multiple';
            $additional_name = '[]';
        }
        
        foreach($column->getOptions() as $key => $value) {
            $selected = '';
            
            if((string)$key === $row->getData($index))
                $selected = 'selected="selected"';
            elseif(is_array($row->getData($index)) && in_array($key, $row->getData($index)))
                $selected = 'selected="selected"';
            
            $options .= '<option value="' . $key . '" ' . $selected . ' >' . $value . '</option>';
        }
        
        return sprintf('<select name="%s[%s]%s" %s width="%s">%s</select>',
                $column->getIndex(),
                $row->getData('id'),
                $additional_name,
                $multiple,
                $column->getWidth(),
                $options);
    }
}
