<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
/**
 * Model Request
 **/
class Modules_UptimeRobot_Model_Request extends Modules_UptimeRobot_Model_Abstract
{
    //create table request(id INTEGER PRIMARY KEY AUTOINCREMENT, state INTEGER default 0 not null, customer_id INTEGER default 1 not null, description TEXT default '' not null, post_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    //CREATE TABLE mappingtable (id integer primary key, guid VARCHAR(30), ur_id VARCHAR(30), url VARCHAR(255), create_datetime INT(11), delete_datetime INT(11) default 0);
    protected $_data = array(
        'id' => null,
        'guid' => null,
        'url' => null,
        'create_datetime' => null,
        'delete_datetime' => null,
    );
    public function __construct($parameters = array())
    {
        parent::__construct();
        foreach($parameters as $param => $value) {
            $this->_data[$param] = $value;
        }
    }
    public function __get($field)
    {
        if(isset($this->_data[$field])) {
            return $this->_data[$field];
        }
        return null;
    }
}