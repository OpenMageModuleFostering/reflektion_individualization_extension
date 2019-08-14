<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To add comment in system config fields
 */

class Reflektion_Catalogexport_Block_Adminhtml_System_Config_CommentText extends Mage_Adminhtml_Block_System_Config_Form_Fieldset

{
   public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $element->getComment();
    }
}
