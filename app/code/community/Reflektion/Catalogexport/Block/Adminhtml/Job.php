<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Admin jobs queue grid layout load
 */
class Reflektion_Catalogexport_Block_Adminhtml_Job extends Mage_Adminhtml_Block_Widget_Grid_Container
{


    public function __construct()
    {

        $this->_controller = "adminhtml_job";
        $this->_blockGroup = "reflektion";
        $this->_headerText = Mage::helper("reflektion")->__("Data Feeds Job Queue");
        parent::__construct();

    }//end __construct()


    protected function _prepareLayout()
    {
        // Remove add button
        $this->_removeButton('add');
        return parent::_prepareLayout();

    }//end _prepareLayout()


}//end class
