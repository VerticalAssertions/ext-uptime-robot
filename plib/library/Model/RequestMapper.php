<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
/**
 * RequestMapper 
 **/

class Modules_UptimeRobot_Model_RequestMapper extends Modules_UptimeRobot_Model_Abstract
{
    public function getMappingTable()
    {
        $sth = $this->_dbh->prepare('SELECT * FROM mappingtable WHERE 1 ORDER BY id');
        $sth->execute();
        $sth->setFetchMode(PDO::FETCH_ASSOC);
        $objects = array();
        while ($row = $sth->fetch()) {
            $objects[$row['guid']] = new Modules_UptimeRobot_Model_Request($row);
        }
        return $objects;
    }

    public function saveMapping(Modules_UptimeRobot_Model_Request $request)
    {
        if (is_null($request->id)) {
            $sth = $this->_dbh->prepare("INSERT INTO mappingtable (guid, ur_id, url, create_datetime, delete_datetime) values (:guid, :ur_id, :url, :create_datetime, :delete_datetime)");
            $sth->bindParam(':guid', $request['guid']);
            $sth->bindParam(':ur_id', $request['ur_id']);
            $sth->bindParam(':url', $request['url']);
            $sth->bindParam(':create_datetime', $request['create_datetime']);
            $sth->bindParam(':delete_datetime', $request['delete_datetime']);
        } else {
            $sth = $this->_dbh->prepare("UPDATE mappingtable SET guid = :guid, ur_id = :ur_id, url = :url, create_datetime = :create_datetime, delete_datetime = :delete_datetime WHERE id = :id");
            $sth->bindParam(':id', $request['id']);
            $sth->bindParam(':guid', $request['guid']);
            $sth->bindParam(':ur_id', $request['ur_id']);
            $sth->bindParam(':url', $request['url']);
            $sth->bindParam(':create_datetime', $request['create_datetime']);
            $sth->bindParam(':delete_datetime', $request['delete_datetime']);
        }
        $res = $sth->execute();
        if (!$res) {
            $error = $sth->errorInfo();
            return "Error: code='{$error[0]}', message='{$error[2]}'.";
        }
        return 0;
    }

    public function deleteMapping(Modules_UptimeRobot_Model_Request $request)
    {
        $sth = $this->_dbh->prepare("DELETE FROM mappingtable WHERE id = :id");
        $sth->bindParam(':id', $request['id']);
        
        $res = $sth->execute();
        if (!$res) {
            $error = $sth->errorInfo();
            return "Error: code='{$error[0]}', message='{$error[2]}'.";
        }
        return 0;
    }
}