<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Validate product attributes to export while saving from admin panel
 */
class Reflektion_Catalogexport_Model_System_Validate_ProductAttributes extends Mage_Core_Model_Config_Data
{

    const ATTRIBUTE_LIMIT = 30;


    public function save()
    {
        $limit = Mage::app()->getWebsite()->getConfig('reflektion_datafeeds/advanced/attribute_limit');
        if (!$limit) {
            $limit = self::ATTRIBUTE_LIMIT;
        }

        $selections = $this->getValue();
        // Config Value
        if (sizeof($selections) > $limit) {
            // more than 30 items selected
            Mage::getSingleton('core/session')->addWarning(
                Mage::helper('reflektion')->__(
                    "WARNING - Only the first %s selected attributes were saved and applied to the feed.",
                    $limit
                )
            );
        }

        $this->setValue(array_slice($selections, 0, $limit, true));
        // only keep the first 30 items the user selected
        return parent::save();

    }//end save()


}//end class
