<?php

/**
 * Admin page.
 * Allows users to login with their email address and pin number.
 * Allows authenticated users to change their email address, pin number or RFID key.
 */

session_start();

require_once('includes/config.php');


// check if this ip address has been banned?
if(is_banned()){
    mail(_ADMIN_NOTIFICATION_EMAIL,'Door Opener - User banned from Admin Page!',var_export($_REQUEST,true));
    echo 'You are banned. Sorry!';
    exit;
}


if(!is_loggedin() && isset($_REQUEST['dologin']) && isset($_REQUEST['email']) && isset($_REQUEST['pin'])){

    // do a pin access attempt
    // this looks up our pin and adds it to the pin_access table.
    $pin = do_pin_access($_REQUEST['pin'],3);
    // grab this pin and check its username matches.
    if($pin && $pin['pin_id'] && $pin['email'] == $_REQUEST['email']){
        // success!
        $_SESSION['_door_loggedin'] = $pin['pin_id'];
        header("Location: admin.php?success");
        exit;
    }else{
        header("Location: admin.php?fail");
        exit;
    }
}else if(is_loggedin() && isset($_REQUEST['doupdate'])){

    $user_id = (int)is_loggedin();
    $sql = "SELECT * FROM `pin` WHERE `pin_id` = ".$user_id;
    $res = mysql_query($sql);
    $user = mysql_fetch_assoc($res);
    if(!$user)logout();

    if($user_id && isset($_REQUEST['name'])&&trim($_REQUEST['name'])){
        $sql = "UPDATE `pin` SET `name` = '".mysql_real_escape_string(trim($_REQUEST['name']))."' WHERE pin_id = $user_id LIMIT 1";
        mysql_query($sql);
    }
    if($user_id && isset($_REQUEST['email'])&&trim($_REQUEST['email'])){
        $sql = "UPDATE `pin` SET `email` = '".mysql_real_escape_string(trim($_REQUEST['email']))."' WHERE pin_id = $user_id LIMIT 1";
        mysql_query($sql);
    }
    if($user_id
        && isset($_REQUEST['current_pin']) && $_REQUEST['current_pin'] == $user['pin']
        && isset($_REQUEST['new_pin1']) && strlen(trim($_REQUEST['new_pin1'])) >= 4
        && isset($_REQUEST['new_pin2']) && strlen(trim($_REQUEST['new_pin2'])) >= 4
        && $_REQUEST['new_pin1'] == $_REQUEST['new_pin2']
    ){
        $sql = "UPDATE `pin` SET `pin` = '".mysql_real_escape_string($_REQUEST['new_pin1'])."' WHERE pin_id = $user_id LIMIT 1";
        mysql_query($sql);
    }
    if($user_id
        && isset($_REQUEST['current_rfid']) && $_REQUEST['current_rfid'] == $user['rfid']
        && isset($_REQUEST['new_rfid1']) && strlen(trim($_REQUEST['new_rfid1'])) >= 4
        && isset($_REQUEST['new_rfid2']) && strlen(trim($_REQUEST['new_rfid2'])) >= 4
        && $_REQUEST['new_rfid1'] == $_REQUEST['new_rfid2']
    ){
        $sql = "UPDATE `pin` SET `rfid` = '".mysql_real_escape_string($_REQUEST['new_rfid1'])."' WHERE pin_id = $user_id LIMIT 1";
        mysql_query($sql);
    }

    header("Location: admin.php?saved");
    exit;
}else if(is_loggedin() && isset($_REQUEST['logout'])){
    logout();
    header("Location: admin.php?loggedout");
    exit;
}

ob_start();
?>
<html>
<head>
    <title>Gold Coast TechSpace - Door Opener Admin</title>
    <style>
        body{
            background-color: #E7E7E7;
            background-image: none;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }
        .wrap{
            width: 700px;
            border:1px solid #CCC;
            padding: 20px;
            background: #FFF;
            margin: 10px auto;
        }
        table{
            border-collapse:collapse;
            margin:9px 0;
        }
        td{
            padding:0.3em 5px;
            font-size:12px;
        }
        th{
            padding:0.3em 5px;
            font-size:12px;
            border:1px solid #000;
            font-weight:normal;
            background:#535353;
            text-align:left;
            color:#FFF;
        }
    </style>
</head>
<body>
<div class="wrap">
    <p align="center">
        <img src="http://gctechspace.org/wp-content/uploads/2011/12/gcts_logo_shadow.png" title="logo" alt="logo">
    </p>

    <?php if(!is_loggedin()){ ?>
    <h2>Login to door controller</h2>
    <form action="" method="post">
        <input type="hidden" name="dologin" value="1">
        <p>Email: <input type="text" name="email" value=""></p>
        <p>Pin: <input type="password" name="pin" value=""></p>
        <p><input type="submit" name="go" value="Login"></p>
    </form>
    <?php }else{
    $user_id = (int)is_loggedin();
    $sql = "SELECT * FROM `pin` WHERE `pin_id` = ".$user_id;
    $res = mysql_query($sql);
    $user = mysql_fetch_assoc($res);
    if(!$user)logout();
    ?>

    <h2>Welcome <?php echo htmlspecialchars($user['name']);?></h2>
        <p><a href="?logout">logout</a></p>

    <form action="" method="post">
        <input type="hidden" name="doupdate" value="1">
        <p>Your details:</p>
        <p>
            Name: <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']);?>">
        </p>
        <p>
            Email: <input type="text" name="email" value="<?php echo htmlspecialchars($user['email']);?>">
        </p>
        <h4>Change Pin:</h4>
        <p>
            Current Pin: <input type="text" name="current_pin" value="">
        </p>
        <p>
            New Pin: <input type="text" name="new_pin1" value="">
        </p>
        <p>
            Repeat: <input type="text" name="new_pin2" value="">
        </p>
        <h4>Change RFID:</h4>
        <p>
            Current RFID: <input type="text" name="current_rfid" value="">
        </p>
        <p>
            New RFID: <input type="text" name="new_rfid1" value="">
        </p>
        <p>
            Repeat: <input type="text" name="new_rfid2" value="">
        </p>
        <p><input type="submit" name="go" value="Save Changes"></p>
    </form>

    <?php } ?>


    <h2> Recent pin authorisation requests: </h2>
    <table>
        <thead>
        <tr>
            <th>Time</th>
            <th>Pin</th>
            <th>Action</th>
            <th>IP Address</th>
        </tr>
        </thead>
        <?php
        $sql = "SELECT * FROM `pin_access` pa ";
        $sql .= " LEFT JOIN `pin` p USING (pin_id)";
        $sql .= " ORDER BY pa.`time` DESC ";
        $sql .= " LIMIT 10 ";
        $res = mysql_query($sql);
        while($row = mysql_fetch_assoc($res)){
            ?>
            <tr>
                <td><?php echo date('Y-m-d H:i:s',$row['time']);?></td>
                <td>
                    <?php if($row['pin_id']){
                    //echo $row['pin'] . " (".$row['name'].")";
                    echo '<em>'.$row['name'].'</em>'. ' <strong>(success!)</strong>';
                }else{
                    //$row['pin_fail']
                    echo '****' . ' <strong>(pin failure!)</strong>';
                } ?>
                </td>
                <td>
                    <?php switch($row['pin_action']){
                    case 1:
                        if(!in_array($row['ip_address'],$allowed_ips)){
                            echo 'Attempted ';
                        }
                        echo "Door Open/Close";
                        break;
                    case 2:
                        if(!in_array($row['ip_address'],$allowed_ips)){
                            echo 'Attempted ';
                        }
                        echo "Checkin";
                        break;
                    case 3:
                        echo "Admin";
                        break;
                    default:
                        echo "N/A";
                        break;
                } ?>
                </td>
                <td>
                    <?php
                    echo preg_replace('#^\d+\.\d+\.#','***.***.',$row['ip_address']);

                    ?>
                </td>
            </tr>
            <?php } ?>
    </table>


</div>
</body>
</html>
