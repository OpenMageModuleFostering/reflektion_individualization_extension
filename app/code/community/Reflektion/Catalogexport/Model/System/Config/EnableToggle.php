<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To enable or disable module and feed generation
 */
class Reflektion_Catalogexport_Model_System_Config_EnableToggle
{


    public function toOptionArray()
    {
        return array(
                array(
                 'value' => 'enabled',
                 'label' => Mage::helper('adminhtml')->__('Enabled'),
                ),
                array(
                 'value' => 'disabled',
                 'label' => Mage::helper('adminhtml')->__('Disabled'),
                ),
               );

    }//end toOptionArray()


}//end class
