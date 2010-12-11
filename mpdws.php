<?php

if (!isset($_POST['salt'])) {
    die ("ERROR: NO AUTH");
}

require("wsfunctions.php");
$wsw = new ws_wrapper();

// Do some pre-settings
$wsw->ws_set($_POST['salt']);

$sec_func = $wsw->ws_dec;
if (isset($_POST['config'])) {
    $wsw->ws_con($sec_func($_POST['config']));
}

$returns = $wsw->ws_run($sec_func($_POST));

echo $returns;

/** End of file **/
