<?php

/*
 * This file handles door PIN/RFID authentication from the ARDUINO:
 *  eg: postback.php?door=open&trigger=1&pin=12345
 *  eg: postback.php?door=closed
 * The ARDUINO posts any PIN numbers or RFID keys to this PHP script for authentication.
 * This PHP script will return a HTTP 200 Success if the code is correct. Or a HTTP 40x if incorrect.
 * The ARDUINO application has the URL of this file hard coded into it.
 *
 * Also when the door status changes (opens or closes) the arduino will post this status change to this script.
 *
 */

mail(_ADMIN_NOTIFICATION_EMAIL,'door postback debug',var_export($_REQUEST,true));

require_once('includes/config.php');

// we only allow hits to this url from our techspace server.
if($_SERVER['REMOTE_ADDR'] != _TECHSPACE_IP){
    mail(_ADMIN_NOTIFICATION_EMAIL,'techspace IP has changed??',$_SERVER['REMOTE_ADDR']."\n".var_export($_REQUEST,true));
    header('HTTP/1.0 400 Error',true,400);
    echo "failed";
    exit;
}

$door_active = 1;

if(isset($_REQUEST['door'])){
    $current_door_status = $_REQUEST['door'];
}else{
    $current_door_status = false;
}
$door_open = 0;
switch($current_door_status){
    case 'open':
        $door_open = 1;
        break;
    case 'closed':
        $door_open = 0;
        break;
    default:
        $door_open = 2; // ??
}
$pin_access_id = 0;
$using_rfid = 0;

if(isset($_REQUEST['trigger'])){



    // someone is triggering the door arduino
    // with a pin code or rfid
    // which pin is this?
    $current_pin = trim($_REQUEST['pin']);
	if(strlen($current_pin)<3)exit;
    $sql = "SELECT * FROM `pin` WHERE `pin` = '".mysql_real_escape_string($current_pin)."' AND `enabled` = 1";
    $res = mysql_query($sql);
    $pin = mysql_fetch_assoc($res);
    $pin_id = 0;
    if($pin && $pin['pin'] == $current_pin){
        // found a pin! woo
        $pin_id = $pin['pin_id'];
    }
    if(!$pin_id || $pin_id < 0){
		// no pin number
		// check for rfid
		$sql = "SELECT * FROM `pin` WHERE `rfid` = '".mysql_real_escape_string($current_pin)."' AND `enabled` = 1";
		$res = mysql_query($sql);
		$pin = mysql_fetch_assoc($res);
		$pin_id = 0;
		if($pin && $pin['rfid'] == $current_pin){
			// found a rfid key! woo
			$using_rfid = 1;
			$pin_id = $pin['pin_id'];
		}
	}
    if(!$pin_id || $pin_id < 0){
        // no pin!
        mail(_ADMIN_NOTIFICATION_EMAIL,'techspace invalid pin from arduino - ON NO!',$_SERVER['REMOTE_ADDR']."\n".var_export($_REQUEST,true));
        header('HTTP/1.0 400 Error',true,400);
        echo "failed pin";
        exit;
    }

    // todo! post the pin_access_id along with the arduino..
    $pin_access_id = isset($_REQUEST['pin_access_id']) ? (int)$_REQUEST['pin_access_id'] : 0;

    if(!$pin_access_id){
        // hacky, see if there
        $sql = "SELECT * FROM `pin_access` WHERE `pin_id` = ".(int)$pin_id." AND `time` > ".(time()-60)." ORDER BY pin_access_id DESC";
        $row = mysql_fetch_assoc(mysql_query($sql));
        if($row && $row['pin_access_id'] && $row['pin_id'] == $pin_id){
            $pin_access_id = $row['pin_access_id'];
        }
    }

}

// or update the latset entry


// check the last status, if it's the same we just update
// the time
$sql = "SELECT * FROM `door_status` ORDER BY `door_status_id` DESC LIMIT 2";
$res = mysql_query($sql);
$last_status1 = mysql_fetch_assoc($res);
$last_status2 = mysql_fetch_assoc($res);

if(!$pin_access_id && $last_status1 && $last_status2 && $last_status1['door_active'] == $door_active && $last_status1['door_open'] == $door_open && $last_status2['door_active'] == $door_active && $last_status2['door_open'] == $door_open){
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
    $sql .= ", `pin_access_id` = ".(int)$pin_access_id."";
    $sql .= ", `rfid` = ".(int)$using_rfid."";
    $sql .= ", `door_open` = ".(int)$door_open."";
    $sql .= ", `door_active` = ".(int)$door_active."";
    mysql_query($sql);


}
/*
// record this status in the log.
$sql = "INSERT INTO `door_status` SET ";
$sql .= " `time` = ".(int)time()."";
$sql .= ", `pin_access_id` = ".(int)$pin_access_id."";
$sql .= ", `door_open` = ".(int)$door_open."";
$sql .= ", `door_active` = ".(int)$door_active."";
mysql_query($sql);

*/

mail(_ADMIN_NOTIFICATION_EMAIL,'techspace door: postback.php',"Received a postback from the arduino\n".var_export($_REQUEST,true)."\n".$sql);

echo "done";
exit;
