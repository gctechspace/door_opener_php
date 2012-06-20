<?php

/*
 * Just a simple PHP script to hit the door and check if Arduino is active, the door is open, or the door is closed.
 */

require_once('includes/config.php');

$status_url = 'http://'._TECHSPACE_IP.':'._TECHSPACE_PORT._TECHSPACE_URI.'get_status';

$ch = curl_init($status_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_HEADER,false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,10);
$data = curl_exec($ch);

// read these status variables from the server.
$door_open = 0;
$door_active = 0;

if(preg_match('#Open or Closed: (.*)#i',$data,$matches)){
    // we got a successful connect.
    switch(strtolower(trim($matches[1]))){
        case 'open':
            $door_open = 1;
            break;
        case 'closed':
            $door_open = 0;
            break;
    }
}

if(preg_match('#Door Status: (.*)#i',$data,$matches)){
    // we got a successful connect.
    switch(strtolower(trim($matches[1]))){
        case 'active':
            $door_active = 1;
            break;
        case 'closed':
            $door_active = 0;
            break;
    }
}


// check the last status, if it's the same we just update
// the time
$sql = "SELECT * FROM `door_status` ORDER BY `door_status_id` DESC LIMIT 2";
$res = mysql_query($sql);
$last_status1 = mysql_fetch_assoc($res);
$last_status2 = mysql_fetch_assoc($res);

if($last_status1 && $last_status2 && $last_status1['door_active'] == $door_active && $last_status1['door_open'] == $door_open && $last_status2['door_active'] == $door_active && $last_status2['door_open'] == $door_open){
    // save status, jus tupdate time.

    // record this status in the log.
    $sql = "UPDATE `door_status` SET ";
    $sql .= " `time` = ".(int)time()."";
    $sql .= " WHERE door_status_id = '".$last_status1['door_status_id']."'";
    mysql_query($sql);


}else{

    // record this status in the log.
    $sql = "INSERT INTO `door_status` SET ";
    $sql .= " `time` = ".(int)time()."";
    $sql .= ", `door_open` = ".(int)$door_open."";
    $sql .= ", `door_active` = ".(int)$door_active."";
    mysql_query($sql);


}

//echo $sql;