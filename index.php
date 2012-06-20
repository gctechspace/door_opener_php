<?php

/**
 * Main door status page. All the info etc.
 * mobile friendly with some basic responsiveness.
 * todo: better design :P
 */

require_once('includes/config.php');

?>
<!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
<title>Door Opener</title>
<style>
body{
	background-color: #E7E7E7;
	background-image: none;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
    margin: 0;
    padding: 0;
}
div#logo{
    text-align: center;
}
.wrap{
    max-width: 900px;
    border:1px solid #CCC;
    padding: 20px;
    background: #FFF;
    margin: 10px auto;
}
table#door_history{
	border-collapse:collapse;
	margin:9px 0;
    width: 100%;
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
tr.odd {
    background-color: #E6E6F6;
}
h2.bubble{
    color: #666;
    background-color: #DFDFDF;
    font-size: 15px;
    margin: 20px 0 0 0;
    text-shadow: white 0 1px 0;
    -moz-border-radius: 6px 6px 0 0;
    -webkit-border-top-right-radius: 6px;
    -webkit-border-top-left-radius: 6px;
    -khtml-border-top-right-radius: 6px;
    -khtml-border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    border-top-left-radius: 6px;
    padding: 9px !important;
    height: 17px !important;
    font-weight: normal !important;
    clear: both !important;
    border: 0 !important;
    display: inline-block;
    width: auto;
}
div.bubble{
    padding: 20px;
    border: 1px solid
    #DFDFDF;
    margin: -1px 0 10px 0;
    background: white;
    -moz-border-radius: 0 0 6px 6px;
    -webkit-border-bottom-right-radius: 6px;
    -webkit-border-bottom-left-radius: 6px;
    -khtml-border-bottom-right-radius: 6px;
    -khtml-border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
    border-bottom-left-radius: 6px;
}
.col_from_time{
    white-space : nowrap;
}
.text_full{}
.text_med{display:none;}
.text_short{display:none;}
/* Tablet Landscape */
@media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {

}
/* Mobile Landscape Size to Tablet Portrait (devices and browsers) */
@media only screen and (min-width: 480px) and (max-width: 767px) {
    .text_full{display:none;}
    .text_med{display:inline;}
    .text_short{display:none;}
    .col_method{display:none;}
    .wrap{padding: 10px;}
    div.bubble{
        padding: 10px;
    }
}
/* Mobile Portrait Size to Mobile Landscape Size (devices and browsers) */
@media only screen and (max-width: 479px) {
    .text_full{display:none;}
    .text_med{display:none;}
    .text_short{display:inline;}
    .col_arduino{display:none;}
    .col_method{display:none;}
    #logo{
        display: none;
    }
    .wrap{padding: 5px;}
    div.bubble{
        padding: 5px;
    }
}
</style>
</head>
<body>
<div class="wrap">
    <div id="logo">
        <img src="http://gctechspace.org/wp-content/uploads/2011/12/gcts_logo_shadow.png" title="logo" alt="logo">
    </div>
    <h1><span class="text_full">Gold Coast TechSpace </span>Door Controller</h1>
    <p>Welcome! <strong>Full members</strong> receive a access to the TechSpace 24/7. They can open the door <a href="/door/open.php">from a web page</a>, using a smartphone app or by using an RFID key tag. Below is a list of recent door activity.</p>

    <table id="door_history">
        <thead>
        <tr>
            <th class="col_from_time">From</th>
            <th class="col_to_time">To</th>
            <th class="col_arduino">Arduino</th>
            <th class="col_door">Door</th>
            <th class="col_total_time">Duration</th>
            <th class="col_method">Method</th>
            <th class="col_notes"></th>
        </tr>
        </thead>
        <?php
        global $c;
        $c=0;
        function status_row($old_status,$new_status,$last=false){
            global $c;
            ?>
            <tr class="<?php echo $c++%2?'odd':'even';?>">
                <td class="col_from_time">
                    <span class="text_full"><?php echo date('D jS M H:i:s',$old_status['time']);?></span>
                    <span class="text_med"><?php echo date('M jS H:i:s',$old_status['time']);?></span>
                    <span class="text_short"><?php echo date('j H:i',$old_status['time']);?></span>
                </td>
                <td class="col_to_time">
                    <span class="text_full"><?php echo date('D jS M H:i:s',$new_status['time']);?></span>
                    <span class="text_med"><?php echo date('jS H:i:s',$new_status['time']);?></span>
                    <span class="text_short"><?php echo date('H:i',$new_status['time']);?></span>
                </td>
                <td class="col_arduino">
                    <?php echo ($new_status['door_active']) ? 'Active' : 'Offline';?>
                </td>
                <td class="col_door">
                    <?php echo $new_status['door_active'] ? (($new_status['door_open']) ? 'Open' : 'Closed') : 'Unknown';?>
                </td>
                <td class="col_total_time">

                    <?php
                    $seconds = $new_status['time'] - $old_status['time'];
                    $minutes = floor($seconds/60);
                    $hours = floor($seconds/3600);
                    if($hours>0){
                        echo "$hours<span class='text_full'> hours</span><span class='text_med'> hrs</span><span class='text_short'>h</span>";
                        $rem = ($seconds/3600) - $hours;
                        echo " " . floor(60*$rem) . "<span class='text_full'> minutes</span><span class='text_med'> mins</span><span class='text_short'>m</span>";
                    }else if($minutes>0){
                        echo $minutes ."<span class='text_full'> minutes</span><span class='text_med'> mins</span><span class='text_short'>m</span>";
                    }else if($seconds>0){
                        echo $seconds."<span class='text_full'> seconds</span><span class='text_med'> secs</span><span class='text_short'>s</span>";
                    }else{
                        echo "0<span class='text_full'> seconds</span><span class='text_med'> secs</span><span class='text_short'>s</span>";
                    }
                    //echo round(($new_status['time'] - $old_status['time'])/3600,2) . ' hours';
                    ?>
                </td>
                <td class="col_method">
                    <?php echo ($new_status['rfid'])? 'RFID':'PIN';?>
                </td>
                <td class="col_notes">
                    <?php switch($new_status['pin_action']){
                    case 1:
                        echo "then ";
                        if($new_status['door_active']){
                            if($new_status['door_open']){
                                if($last){
                                    echo 'half closed';
                                }else{
                                    echo 'closed';
                                }
                            }else{
                                echo 'opened';
                            }
                        }
                        //echo $new_status['door_active'] ? (($new_status['door_open']) ? 'closed' : 'opened') : '';
                        echo " by ";
                        echo $new_status['name'];
                        break;
                    case 2:
                        echo "Checkin by ";
                        echo $new_status['name'];
                        break;
                    default:
                        //echo ".. and still ";
                        //echo $new_status['door_active'] ? (($new_status['door_open']) ? 'open' : 'closed') : 'Unknown';
                        break;
                } ?>
                </td>
            </tr>
            <?php
        }
        $sql = "SELECT ds.*,p.`name`, p.`pin_id`, pa.pin_action FROM `door_status` ds ";
        $sql .= " LEFT JOIN `pin_access` pa USING (pin_access_id)";
        $sql .= " LEFT JOIN `pin` p USING (pin_id)";
        $sql .= " ORDER BY ds.`time` ASC ";
//$sql .= " LIMIT 20 ";
        $limit = 30;
        $res = mysql_query($sql);
        $old_status = $old_status_begin = false;
        $count = mysql_num_rows($res);
        $x=0;
        while($new_status = mysql_fetch_assoc($res)){
            $x++;
            if($x<$count-$limit)continue;
            // loop through until we find a change in status, then we print this out.
            if(!$old_status_begin){ // seed
                $old_status = $old_status_begin = $new_status;
            }
            if(
                $old_status['door_open'] != $new_status['door_open']
                ||
                $old_status['door_active'] != $new_status['door_active']
            ){
                // we have a new status change! print out a log entry.
                status_row($old_status_begin,$old_status);
                $old_status_begin = $new_status;
            }
            $old_status = $new_status;
        }
// finish.
        status_row($old_status_begin,$old_status,true);
        ?>
    </table>

    <div class="block" id="system_info">
        <h2 class="bubble">System Information:</h2>
        <div class="bubble">
            <p>
                Door API details are available on the wiki: <br/>
                <a href="http://gctechspace.org/doku.php?id=android_door_entry_system_via_wifi#api">http://gctechspace.org/doku.php?id=android_door_entry_system_via_wifi#api</a>
                <br/><br/>
                These will grow over time as the system matures.
            </p>
            <p>
                IP Addresses that are allowed to open door: <?php echo implode(", ",$allowed_ips);?>
                (ie: only connections from techspace wifi)
            </p>
            <p>
                Current server time: <?php echo date('Y-m-d H:i:s');?>
            </p>
            <p>
                4 incorrect tries in 3 minutes = temporary ip address ban.
            </p>
            <p>
                Current members with active door access: <strong><?php
                $sql = "SELECT * FROM `pin`WHERE `enabled` = 1 ORDER BY `name`";
                $res = mysql_query($sql);
                $pins = array();
                while($pin = mysql_fetch_assoc($res)){
                    //$pins[] = $pin['pin'] .' ('.$pin['name'].')';
                    $pins [] = $pin['name'];
                }
                echo implode(', ',$pins);
                ?>
            </strong>
            </p>
        </div>
    </div>


    <h2 class="bubble">HTML5 web app:</h2>
    <div class="bubble">
        <p>
            <img src="qr_webapp.png" width="180"> <br/>
            You can type your membership Pin number into this web application to open the door. <br/>
            <a href="http://gctechspace.org/door/open.php">http://gctechspace.org/door/open.php</a>
        </p>
        <div class="clear"></div>
    </div>


    <div class="block" id="download_android_info">
        <h2 class="bubble">Download the Android app:</h2>
        <div class="bubble">
            <p>
                <img src="qr.png" width="180"> <br>
                <a href="http://gctechspace.org/door/techspace_phoneapp.apk">http://gctechspace.org/door/techspace_phoneapp.apk</a>
                <br><br>
                More information about the android app on the wiki:<br> <a href="http://gctechspace.org/doku.php?id=android_door_entry_system_via_wifi">http://gctechspace.org/doku.php?id=android_door_entry_system_via_wifi</a>
            </p>
            <div class="clear"></div>
        </div>
    </div>



    <div class="block" id="download_ios_info">
        <h2 class="bubble">iPhone/ios app:</h2>
        <div class="bubble">
            <p>In progress. (edit: probably wont happen now we have the html5 web app)</p>
            <div class="clear"></div>
        </div>
    </div>

</div>
</body>
</html>
