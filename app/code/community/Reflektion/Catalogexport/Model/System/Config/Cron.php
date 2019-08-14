<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Validate and save cron job frequency
 */
class Reflektion_Catalogexport_Model_System_Config_Cron extends Mage_Core_Model_Config_Data
{

    const CRON_STRING_PATH = 'crontab/jobs/reflektion_processdailyfeeds/schedule/cron_expr';


    protected function _afterSave()
    {
        $time = $this->getData('groups/configurable_cron/fields/frequency/value');
        $time = explode(" ", $time);
        try {
            if (count($time) == 5) {
                foreach ($time as $val)
                if (preg_match('/[^*,\/0-9]/i', $val)) {
                    throw new Exception();
                }
            } else {
                throw new Exception();
            }

            $cronExprArray = array(
                              $time[0],
            // Minute
                              $time[1],
            // Hour
                              $time[2],
            // Day of the Month
                              $time[3],
            // Month of the Year
                              $time[4],
            // Day of the Week
                             );
            $cronExprString = join(' ', $cronExprArray);

            Mage::getModel('core/config_data')
                    ->load(self::CRON_STRING_PATH, 'path')
                    ->setValue($cronExprString)
                    ->setPath(self::CRON_STRING_PATH)
                    ->save();
        } catch (Exception $e) {
            throw new Exception(Mage::helper('cron')->__('Unable to save the cron expression.'));
        }//end try

    }//end _afterSave()


}//end class
