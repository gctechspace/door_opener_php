<?php
//test

require_once('includes/config.php');

?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Door Opener - GC TechSpace</title>
    <link rel="stylesheet" href="http://code.jquery.com/mobile/1.0/jquery.mobile-1.0.min.css" />
    <script src="http://code.jquery.com/jquery-1.6.4.min.js"></script>
    <script src="http://code.jquery.com/mobile/1.0/jquery.mobile-1.0.min.js"></script>
    <script type="text/javascript">
        $(document).bind("mobileinit", function(){
            $.mobile.ajaxEnabled = false;
        });
    </script>
</head>
<body>

<div data-role="page">

    <div data-role="header">
        <h1>Door Opener - GC TechSpace</h1>
    </div><!-- /header -->

    <div data-role="content">

        <?php
		$user_pin = '';
        if(isset($_REQUEST['pin_action']) && isset($_REQUEST['c'])){

            $user_pin = $_REQUEST['c'];
            // friendly error checking before passing to our c.php script for processing.
            if(strlen($user_pin) <= 0 || strlen($user_pin) > 10){
                mail(_ADMIN_NOTIFICATION_EMAIL,'Door Opener: invalid pin from webapp',var_export($_REQUEST,true));
                echo "Pin Failure";
            }else if(is_banned()){
                mail(_ADMIN_NOTIFICATION_EMAIL,'Door Opener: webapp user is banned',var_export($_REQUEST,true));
                echo "Banned, please wait.";
            }else{
                // call the same script that the
                include('c.php');
            }
        }
        ?>

        <p>
            <?php
            ob_start();
            include('status.php');
            echo nl2br(ob_get_clean());
            ?>
        </p>

        <form action="open.php" method="post" id="pinform" data-ajax="false">
            <input type="hidden" name="pin_action" value="" id="trigger">

            <div data-role="fieldcontain">
                <label for="pin">Your PIN:</label>
                <input type="password" name="c" id="pin" value="<?php echo htmlspecialchars($user_pin);?>" data-ajax="false" />
            </div>

        </form>
        <fieldset class="ui-grid-a">
            <div class="ui-block-a"><button type="submit" data-theme="c" data-icon="check" onclick="$('#trigger').val(2); $('#pinform')[0].submit(); return false;">Checkin</button></div>
            <div class="ui-block-b"><button type="submit" data-theme="b" data-icon="check" onclick="$('#trigger').val(1); $('#pinform')[0].submit(); return false;">Door</button></div>
        </fieldset>

    </div><!-- /content -->

</div><!-- /page -->


</body>
</html>
