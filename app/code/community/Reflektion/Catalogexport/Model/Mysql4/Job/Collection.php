<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 */
class Reflektion_Catalogexport_Model_Mysql4_Job_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{


    public function _construct()
    {
        $this->_init('reflektion/job');

    }//end _construct()


}//end class
