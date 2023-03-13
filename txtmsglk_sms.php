<?php

/**
 * Plugin Name:       Txtmsg
 * Description:       Send Woocommerce order updates to customers/admin via txtmsg.lk SMS Gateway. 
 * Version:           1.0
 * Author:            ASIT Solutions
 * Text Domain:       ASIT
 * Author URI:        https://asit.pw/
 * Dashboard URI:     https://sms.txtmsg.lk/
 * License:           GPLv2 or later
 */

include 'includes/core-import.php';
new txtmsgSMS(__FILE__);
