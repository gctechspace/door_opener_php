<?php

/**
 * Quick bunch of PHP functions.
 * todo: put this into a nicer PHP class.
 */

// basic admin script to allow pin changes.
function is_loggedin(){
    return isset($_SESSION['_door_loggedin'])&&$_SESSION['_door_loggedin'] ? $_SESSION['_door_loggedin'] : false;
}
function logout(){
    $_SESSION['_door_loggedin'] = false;
    header("Location: admin.php");
    exit;
}

/**
 * Simply checks if the remote IP address is banned.
 * ie: they tried too many failed attempts within a few minutes.
 * @return bool
 */
function is_banned(){
    // over past 3 minutes.
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $sql = "SELECT COUNT(*) as `c` FROM `pin_access` WHERE `ip_address` = '".mysql_real_escape_string($ip_address)."' AND (pin_id = 0 OR pin_fail IS NOT NULL) and `time` > ".(time() - 180);
    $res = mysql_query($sql) or die(mysql_error());
    $row = mysql_fetch_assoc($res);
    if($row && $row['c'] > 3){
        return true; // they are banned
    }
    return false;
}

/**
 * @param string $user_pin - Provided pin number or RFID key
 * @param int $pin_action - What the user is doing (checkin = 2, door open = 1, admin login = 3)
 * @return array|bool
 */
function do_pin_access($user_pin,$pin_action=2){

    if(trim($user_pin)){
        $ip_address = $_SERVER['REMOTE_ADDR'];
        // find pin in DB
        $sql = "SELECT * FROM `pin` WHERE `pin` = '".mysql_real_escape_string($user_pin)."' AND `enabled` = 1";
        $res = mysql_query($sql);
        $pin = mysql_fetch_assoc($res);
        if($pin && $pin['pin'] == $user_pin){
            // found a pin! woo
            $pin_id = $pin['pin_id'];
        }else{
            $pin_id = 0; // still log an empty attempt in the database.
        }
        $sql = "INSERT INTO `pin_access` SET ";
        $sql .= " `time` = ".time()." ";
        $sql .= " , `pin_id` = ".(int)$pin_id." ";
        $sql .= " , `pin_action` = ".(int)$pin_action." ";
        $sql .= " , `ip_address` = '".mysql_real_escape_string($ip_address)."'";
        if(!$pin_id){
            $sql .= " , pin_fail = '".mysql_real_escape_string($user_pin)."'";
        }
        mysql_query($sql);
        $pin_access_id = mysql_insert_id();

        if($pin && $pin['pin'] == $user_pin){
            $pin['pin_access_id'] = $pin_access_id;
        }else{
            $pin = false;
        }
        return $pin;
    }

    return false;
}


/*GOOGLE C2DM stuff*/
function send_c2dm_message($deviceRegistrationId,$message_data){
    $username = _GOOGLE_C2DM_EMAIL;
    $password = _GOOGLE_C2DM_PASSWORD;
    $source = 'techspace-phoneapp-1.0';

    $data = array(
        'registration_id' => $deviceRegistrationId,
        //'data.msg' => $message,
    );
    $collapse_key = md5(time()); // unique key to combine messages..

    if(is_array($message_data)){
        // grab out the fields for this message
        $data['data.message'] = $message_data['message'];
        $collapse_key = md5(time().$message_data['message']);
        $authcode = googleAuthenticate($username,$password,$source);
        $res = sendMessageToPhone($authcode,$deviceRegistrationId,$collapse_key,$data);
        return $res;
    }
    return false;
}
function sendMessageToPhone($authCode, $deviceRegistrationId, $collapse_key, $data) {

    $headers = array('Authorization: GoogleLogin auth=' . $authCode);
    $data['collapse_key'] = $collapse_key;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://android.apis.google.com/c2dm/send");
    if ($headers)
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
}
function googleAuthenticate($username, $password, $source="Company-AppName-Version", $service="ac2dm") {

    $cache_file = 'google.cache';
    if(is_file($cache_file) && filemtime($cache_file) > time()-800){
        // cache file was modified after 800 seconds ago - so it's new
        return file_get_contents($cache_file);
    }

    // get an authorization token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
    $post_fields = "accountType=" . urlencode('HOSTED_OR_GOOGLE')
        . "&Email=" . urlencode($username)
        . "&Passwd=" . urlencode($password)
        . "&source=" . urlencode($source)
        . "&service=" . urlencode($service);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // for debugging the request
    //curl_setopt($ch, CURLINFO_HEADER_OUT, true); // for debugging the request

    $response = curl_exec($ch);

    //var_dump(curl_getinfo($ch)); //for debugging the request
    //var_dump($response);

    curl_close($ch);

    if (strpos($response, '200 OK') === false) {
        return false;
    }

    // find the auth code
    preg_match("/(Auth=)([\w|-]+)/", $response, $matches);

    if (!$matches[2]) {
        return false;
    }

    file_put_contents($cache_file,$matches[2]);
    return $matches[2];
}
