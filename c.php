<?php

/*
 * This file handles door PIN authentication from the ANDROID app and the WEB app.
 * The android app posts any PIN numbers to this PHP script for authentication.
 * This PHP script will return a HTTP 200 Success if the code is correct. Or a HTTP 40x if incorrect.
 * The android application has the URL of this file hard coded into it.
 *
 * The web app (open.php) also simply includes this file to trigger door open requests.
 */

require_once('includes/config.php');

// check for required fields:
if(!isset($_REQUEST['c']) || !strlen($_REQUEST['c']) || !isset($_REQUEST['pin_action']) || !(int)$_REQUEST['pin_action']){
    mail(_ADMIN_NOTIFICATION_EMAIL,'Door Opener: Data Error',var_export($_REQUEST,true));
    // the arduino is sending incorrect data! Oh no!
    header('HTTP/1.0 406 Invalid Data',true,406);
    exit;
}

$user_pin = $_REQUEST['c'];
$pin_action = (int)$_REQUEST['pin_action'];
$ip_address = $_SERVER['REMOTE_ADDR'];

// we only allow connections to this PHP script from the arduino at the techspace.
if($ip_address != _TECHSPACE_IP){
    mail(_ADMIN_NOTIFICATION_EMAIL,'Door Opener: Arduino has incorrect IP: '.$ip_address,var_export($_REQUEST,true));
    header('HTTP/1.0 406 Invalid IP Address',true,406);
    exit;
}

if(strlen($user_pin) <= 0 || strlen($user_pin) > 20){
    mail(_ADMIN_NOTIFICATION_EMAIL,'Door Opener: invalid pin number sent.'.$ip_address,var_export($_REQUEST,true));
    header('HTTP/1.0 406 Invalid Pin',true,406);
    exit;
}

if(is_banned()){
    mail(_ADMIN_NOTIFICATION_EMAIL,'Door Opener: Oh no! The arduino got banned!'.$ip_address,var_export($_REQUEST,true));
    header('HTTP/1.0 407 Banned',true,407);
    exit;
}

$pin = do_pin_access($user_pin,$pin_action);
if($pin && isset($pin['pin_id']) && $pin['pin_id']){

    if(in_array($ip_address,$allowed_ips)){
        $broadcast_message = "";
        $broadcast_message .= $pin['name'];
        $send_broadcast = false;
        switch($pin_action){
            case 1:
                if(in_array($ip_address,$allowed_ips)){
                    // hit up our arduino, tell it to open the door with a pin number.
                    $trigger_url = 'http://'._TECHSPACE_IP.':'._TECHSPACE_PORT._TECHSPACE_URI.'triggerdoor?pin='.$user_pin.'&go';
                    $ch = curl_init($trigger_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
                    curl_setopt($ch, CURLOPT_HEADER,false);
                    $data = curl_exec($ch);
                    mail(_ADMIN_NOTIFICATION_EMAIL,'door trigger success',$user_pin .' - '.$data."\n".var_export($pin,true));
                    $broadcast_message .= " just triggered door.";
                    $send_broadcast = true;
                }
                break;
            case 2:
                $broadcast_message .= " is at the space.";
                $send_broadcast = true;
                break;
        }
        if($send_broadcast){
            // send message to everyone who is registered.
            $sql = "SELECT * FROM `c2dm` WHERE `end_time` = 0";
            $res = mysql_query($sql);
            while($row = mysql_fetch_assoc($res)){
                if($row['c2dm_key']){
                    send_c2dm_message($row['c2dm_key'],array('message'=>$broadcast_message));
                }
            }
        }
        // send 200 header back (default)
        echo "Success!";
    }else{
        header('HTTP/1.0 405 Wifi Incorrect',true,405);
        echo "FAIL: YOU ARE NOT ON THE TECHSPACE WIFI !";
    }
}else{
    // send some other code rather than 200 back
    header('HTTP/1.0 405 Wrong Pin',true,405);
    echo "FAIL: INCORRECT PIN ENTERED !";
}
