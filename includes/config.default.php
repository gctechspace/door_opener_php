<?php

// rename this file to config.php 

ini_set('display_errors',true);
ini_set('error_reporting',E_ALL);

include('functions.php');

date_default_timezone_set('Australia/Brisbane'); // our timezone

@mysql_connect('localhost','username','password'); // database login
mysql_select_db('database'); // database name
// see install.sql for database structure.

define('_ADMIN_NOTIFICATION_EMAIL','you@email.com');
define('_TECHSPACE_IP','220.233.47.13'); // add the IP address of your Arduino here
define('_TECHSPACE_PORT','8082'); // add the port number your Arduino is listening on (or forward through from router)
define('_TECHSPACE_URI','/'); // leave as /
define('_GOOGLE_C2DM_EMAIL',''); // your google email address that is registered for c2dm (make a new one just for c2dm)
define('_GOOGLE_C2DM_PASSWORD',''); // password for above google account.


// array used in a few of the php scripts.
// todo: push this into a method is_ip_allowed($ip) or something
// list of IP addresses that are allowed to trigger the door open
$allowed_ips = array(
    _TECHSPACE_IP
);

