<?php

/**
 * This PHP script is called form the Android application
 * when the android application registers or deregisters a C2DM request.
 */
require_once('includes/config.php');

$c2dm_key = isset($_REQUEST['c2dm_key']) ? $_REQUEST['c2dm_key'] : false;
if($c2dm_key){
    if(isset($_REQUEST['register'])){
        // user is registering with c2dm.
        // store their new entry in the db (if that key doesn't already exists)
        $sql = "REPLACE INTO `c2dm` SET ";
        $sql .= " `start_time` = '".time()."'";
        $sql .= ", `end_time` = 0";
        $sql .= ", `notify_type` = 1"; // 1 is door? maybe allow different notification levels down the track.
        $sql .= ", `c2dm_key` = '".mysql_real_escape_string($c2dm_key)."'";
        mysql_query($sql) or die(mysql_error());
        echo 'registered.';

    }else if(isset($_REQUEST['unregister'])){
        $sql = "UPDATE `c2dm` SET `end_time` = '".time()."' WHERE c2dm_key = '".mysql_real_escape_string($c2dm_key)."'";
        mysql_query($sql);
        echo 'unregistered.';
    }
}
