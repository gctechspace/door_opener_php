<?php

/*
 * The android application calls this URL and simply outputs any text
 * to the android application.
 * Shows the current door status.
 * This status is also shown within open.php (the web app)
 */

require_once('includes/config.php');


if(is_banned()){
    echo "Sorry, too many failed attempts. Wait a few minutes.";
    exit;
}


$client_ip = $_SERVER['REMOTE_ADDR'];

// todo - check we can connect to door controller.
// look at the last history in our door log.
$sql = "SELECT * FROM `door_status` ORDER BY `door_status_id` DESC LIMIT 1";
$last_status = mysql_fetch_assoc(mysql_query($sql));
$door_active = false;
$door_open = false;
$last_status_time = false;
if($last_status){
    $door_active = (int)$last_status['door_active'];
    $door_open = (int)$last_status['door_open'];
    $last_status_time = $last_status['time'];
}

$status = "The door is ";

if($door_active){
    if($door_open){
        $status .= "open";
    }else{
        $status .= "closed";
    }
	if(!isset($_REQUEST['os'])){
		$status .= " as of ".date('d/m/Y H:i T',$last_status_time).".\n\n";
	}
	if(!isset($_REQUEST['os'])){
		if(in_array($client_ip,$allowed_ips)){
			$status .= "Please input your pin.";
		}else{
			$status .= "PLEASE CONNECT TO THE TECHSPACE WIFI !";
		}
	}
}else{
	$status = "INACTIVE, sorry.";
}

echo $status;

