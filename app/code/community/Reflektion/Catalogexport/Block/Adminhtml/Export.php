<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Admin export grid layout load
 */
class Reflektion_Catalogexport_Block_Adminhtml_Export extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {

        $this->_controller = "adminhtml_export";
        $this->_blockGroup = "reflektion";
        $this->_headerText = Mage::helper("reflektion")->__("Generate Data Feeds");
        parent::__construct();
    }

    protected function _prepareLayout() {
        // Remove add button
        $this->_removeButton('add');

        // Export all button
        $this->_addButton('exportall', array(
            'label' => 'Export Feeds For All Sites',
            'onclick' => 'setLocation(\'' . $this->getUrl('*/*/exportall') . '\')',
            'class' => 'exportall',
        ));

        return parent::_prepareLayout();
    }

}
