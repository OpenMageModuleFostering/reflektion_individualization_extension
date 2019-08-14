<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Prepare collection and select columns 
 */
class Reflektion_Catalogexport_Block_Adminhtml_Job_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId("jobGrid");
        $this->setDefaultSort('job_id');
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
        if (Mage::registry('preparedFilter')) {
            $this->setDefaultFilter(Mage::registry('preparedFilter'));
        }
    }

    protected function _addColumnFilterToCollection($column) {
        $filterArr = Mage::registry('preparedFilter');
        if (($column->getId() === 'store_id' || $column->getId() === 'status') && $column->getFilter()->getValue() && strpos($column->getFilter()->getValue(), ',')) {
            $_inNin = explode(',', $column->getFilter()->getValue());
            $inNin = array();
            foreach ($_inNin as $k => $v) {
                if (is_string($v) && strlen(trim($v))) {
                    $inNin[] = trim($v);
                }
            }
            if (count($inNin) > 1 && in_array($inNin[0], array('in', 'nin'))) {
                $in = $inNin[0];
                $values = array_slice($inNin, 1);
                $this->getCollection()->addFieldToFilter($column->getId(), array($in => $values));
            } else {
                parent::_addColumnFilterToCollection($column);
            }
        } elseif (is_array($filterArr) && array_key_exists($column->getId(), $filterArr) && isset($filterArr[$column->getId()])) {
            $this->getCollection()->addFieldToFilter($column->getId(), $filterArr[$column->getId()]);
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    protected function _prepareCollection() {
        $collection = Mage::getResourceModel('reflektion/job_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('job_id', array(
            'header' => Mage::helper('reflektion')->__('Job ID'),
            'index' => 'job_id'
        ));

        $this->addColumn('website_id', array(
            'header' => Mage::helper('reflektion')->__('Website ID'),
            'index' => 'website_id'
        ));

        $this->addColumn('type', array(
            'header' => Mage::helper('reflektion')->__('Job Type'),
            'index' => 'type',
            'type' => 'options',
            'options' => array(
                Reflektion_Catalogexport_Model_Job::TYPE_GENERATE_BASELINE => Mage::helper('reflektion')->__('Manual Feed'),
                Reflektion_Catalogexport_Model_Job::TYPE_GENERATE_DAILY => Mage::helper('reflektion')->__('Daily Feed'),
                Reflektion_Catalogexport_Model_Job::TYPE_TRANSFER => Mage::helper('reflektion')->__('Transfer File'),
                Reflektion_Catalogexport_Model_Job::TYPE_TRANSFER_MANUAL => Mage::helper('reflektion')->__('Transfer File Manual'),
            ),
        ));

        $this->addColumn('feed_type', array(
            'header' => Mage::helper('reflektion')->__('Feed Type'),
            'index' => 'feed_type'
        ));

        $this->addColumn('scheduled_at', array(
            'header' => Mage::helper('reflektion')->__('Scheduled'),
            'type' => 'datetime',
            'index' => 'scheduled_at'
        ));

        $this->addColumn('ended_at', array(
            'header' => Mage::helper('reflektion')->__('Completed'),
            'type' => 'datetime',
            'width' => '160px',
            'index' => 'ended_at'
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('reflektion')->__('Status'),
            'width' => '100px',
            'index' => 'status',
            'type' => 'options',
            'options' => array(
                Reflektion_Catalogexport_Model_Job::STATUS_SCHEDULED => Mage::helper('reflektion')->__('Scheduled'),
                Reflektion_Catalogexport_Model_Job::STATUS_RUNNING => Mage::helper('reflektion')->__('Running'),
                Reflektion_Catalogexport_Model_Job::STATUS_COMPLETED => Mage::helper('reflektion')->__('Completed'),
                Reflektion_Catalogexport_Model_Job::STATUS_ERROR => Mage::helper('reflektion')->__('Error'),
                Reflektion_Catalogexport_Model_Job::STATUS_MANUAL => Mage::helper('reflektion')->__('Manual'),
            ),
        ));
        return parent::_prepareColumns();
    }

    protected function _prepareMassaction() {
        $this->setMassactionIdField('job_id');
        $this->getMassactionBlock()->setFormFieldName('job_id');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('reflektion')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('reflektion')->__('Delete selected job(s)?')
        ));

        $this->getMassactionBlock()->addItem('execute', array(
            'label' => Mage::helper('reflektion')->__('Run Job'),
            'url' => $this->getUrl('*/*/massRun'),
            'confirm' => Mage::helper('reflektion')->__('Run selected job(s)?  Note that running multiple and/or jobs may impact site performance.')
        ));

        return $this;
    }

}
