<?php

require_once('mpdfunctions.php');

$mcon = new mpd_connection();
$return = $mcon->mpd_send_com('list', 'artist');

print_r($return);
