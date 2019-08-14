<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Prepare collection and select columns 
 */
class Reflektion_Catalogexport_Block_Adminhtml_Export_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId("exportGrid");
        $this->setDefaultDir("ASC");
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        // Collection for each data feed for each website
        $collection = new Varien_Data_Collection();

        // All websites
        $websites = Mage::app()->getWebsites(false, true);
        foreach ($websites as $website) {
            $websiteId = $website->getId();

            if (Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/general/allfeedsenabled') == 'enabled') {

                $feedTypes = '';
                foreach (Reflektion_Catalogexport_Model_Generatefeeds::getFeedTypes() as $curFeedType) {
                    if (Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/feedsenabled/' . $curFeedType) == 'enabled') {
                        if (strlen($feedTypes) > 0) {
                            $feedTypes .= ', ';
                        }
                        $feedTypes .= $curFeedType;
                    }
                }
                $sftpUser = Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/connect/username');
                $sftpDestination = Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/connect/hostname');

                // Grid item
                $newItem = $collection->getNewEmptyItem();
                $newItem->setData(array(
                    'id' => $website->getId(),
                    'website_name' => $website->getName(),
                    'website_code' => $website->getCode(),
                    'feeds' => $feedTypes,
                    'sftp_destination' => $sftpDestination,
                    'sftp_user' => $sftpUser,
                ));
                $collection->addItem($newItem);
            }
        }

        $this->setCollection($collection);
        return $this;
    }

    protected function _prepareColumns() {
        $this->addColumn('id', array(
            'header' => Mage::helper('reflektion')->__('Website ID'),
            'width' => '50px',
            'index' => 'id'
        ));

        $this->addColumn('website_name', array(
            'header' => Mage::helper('reflektion')->__('Website Name'),
            'width' => '110px',
            'index' => 'website_name'
        ));

        $this->addColumn('website_code', array(
            'header' => Mage::helper('reflektion')->__('Website Code'),
            'width' => '100px',
            'index' => 'website_code'
        ));

        $this->addColumn('feeds', array(
            'header' => Mage::helper('reflektion')->__('Feeds to Send'),
            'width' => '320px',
            'index' => 'feeds'
        ));

        $this->addColumn('sftp_destination', array(
            'header' => Mage::helper('reflektion')->__('SFTP Destination'),
            'width' => '140px',
            'index' => 'sftp_destination'
        ));

        $this->addColumn('sftp_user', array(
            'header' => Mage::helper('reflektion')->__('SFTP User'),
            'width' => '100px',
            'index' => 'sftp_user'
        ));

        $this->addColumn('action', array(
            'header' => Mage::helper('reflektion')->__('Action'),
            'filter' => false,
            'sortable' => false,
            'renderer' => 'reflektion/adminhtml_export_grid_renderer_action'
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
        return $this->getUrl("*/*/exportone", array("id" => $row->getId()));
    }

}
