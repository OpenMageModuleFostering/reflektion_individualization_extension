<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Render action to generate data feed queue
 */
class Reflektion_Catalogexport_Block_Adminhtml_Export_Grid_Renderer_Action extends
    Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{


    public function render(Varien_Object $row)
    {
        $this->getColumn()->setActions(
            array(array(
                   'url'     => $this->getUrl('*/*/exportone', array('id' => $row->getId())),
                   'caption' => Mage::helper('reflektion')->__('Export Feed For '.$row->getWebsiteName()),
                  ),
            )
        );
        return parent::render($row);

    }//end render()


}//end class
