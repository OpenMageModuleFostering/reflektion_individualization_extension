<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Creating table to record job queue
 */
$installer = $this;
$installer->startSetup();
$table = $installer->getConnection()->newTable($installer->getTable('reflektion/job'))
    ->addColumn(
        'job_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
         'unsigned' => true,
         'nullable' => false,
         'primary'  => true,
         'identity' => true,
        ),
        'Job Id'
    )
    ->addColumn(
        'website_id',
        Varien_Db_Ddl_Table::TYPE_SMALLINT,
        6,
        array(
         'unsigned' => true,
         'nullable' => false,
        ),
        'Website Id'
    )
    ->addColumn(
        'dependent_on_job_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array('unsigned' => true),
        'Dependent On Job Id'
    )
    ->addColumn(
        'min_entity_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array('unsigned' => true),
        'Min Entity Id'
    )
    ->addColumn(
        'type',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        40,
        array('nullable' => false),
        'Type'
    )
    ->addColumn(
        'feed_type',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        40,
        array('nullable' => false),
        'Feed Type'
    )
    ->addColumn(
        'status',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        11,
        array('nullable' => false),
        'Status'
    )
    ->addColumn(
        'scheduled_at',
        Varien_Db_Ddl_Table::TYPE_DATETIME,
        null,
        array('nullable' => true),
        'Scheduled At'
    )
    ->addColumn(
        'started_at',
        Varien_Db_Ddl_Table::TYPE_DATETIME,
        null,
        array('nullable' => true),
        'Started At'
    )
    ->addColumn(
        'ended_at',
        Varien_Db_Ddl_Table::TYPE_DATETIME,
        null,
        array('nullable' => true),
        'Ended At'
    )
    ->addColumn(
        'error_message',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        64,
        array('nullable' => false),
        'Error Message'
    )
    ->addIndex(
        $installer->getIdxName(
            'reflektion/job',
            array(
             'type',
             'feed_type',
             'status',
            )
        ),
        array(
         'type',
         'feed_type',
         'status',
        )
    )
    ->setComment('reflektion/job entity table');
$installer->getConnection()->createTable($table);

$installer->endSetup();
