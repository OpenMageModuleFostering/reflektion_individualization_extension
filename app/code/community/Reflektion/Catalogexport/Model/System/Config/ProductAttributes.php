<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To fetch all user defined attributes
 */
class Reflektion_Catalogexport_Model_System_Config_ProductAttributes
{


    public function toOptionArray()
    {

        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addFieldToFilter('is_user_defined', 1)
                ->addVisibleFilter()
                ->addStoreLabel(Mage::app()->getStore()->getId())
                ->setOrder('main_table.attribute_id', 'asc');
        $result     = array();
        foreach ($attributes as $_attribute) {
            $result[] = array(
                         'value' => $_attribute["attribute_code"],
                         'label' => $_attribute["frontend_label"],
                        );
        }

        return $result;

    }//end toOptionArray()


}//end class
