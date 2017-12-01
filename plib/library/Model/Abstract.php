<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
/**
 * Abstract Model
 **/

abstract class Modules_UptimeRobot_Model_Abstract
{
    protected $_dbh = null;

    
    const UR_DB_FILE = 'uptimerobot.db';

    public function __construct()
    {
    	define('UR_DATA_PATH', pm_Context::getVarDir()); // /opt/psa/var/modules/uptime-robot/;
    	define('UR_DB_PATH', pm_Context::getVarDir() . DIRECTORY_SEPARATOR . self::UR_DB_FILE);

    	// Create Database if does not exists
    	if(!is_writable(UR_DATA_PATH)) {
    		$this->_status = array('error' => array('setupDatadirNotWritable', [ 'datapath' => UR_DATA_PATH ]));
        }
        elseif(!file_exists(UR_DB_PATH)) {
            try {
                $db = new SQLite3(UR_DB_PATH);

                $this->_status = array('info' => array('setupDatabaseCreated', [ ]));
            }
            catch(Exception $e) {
                echo $e->getMessage();
                $this->_status = array('error' => array('setupUnableToCreateDatabase', [ 'dbpath' => UR_DB_PATH, 'errormsg' => $e->getMessage() ]));
            }
        }
        elseif(!is_writable(UR_DB_PATH)) {
            $this->_status = array('error' => array('setupDatabaseNotWritable', [ 'dbpath' => UR_DB_PATH ]));
        }

        $this->_dbh = new PDO('sqlite:' . UR_DB_PATH);

        // Create mapping table
        $sth = $this->_dbh->prepare('CREATE TABLE IF NOT EXISTS mappingtable (id integer primary key, guid VARCHAR(30), ur_id VARCHAR(30), url VARCHAR(255), create_datetime INT(11), delete_datetime INT(11) default 0)');
        $res = $sth->execute();
        if(!$res) {
            $error = $sth->errorInfo();
            $this->_status = array('error' => array('setupUnableToCreateMappingTable', [ 'errormsg' => "code='{$error[0]}', message='{$error[2]}'" ]));
        }
    }
}