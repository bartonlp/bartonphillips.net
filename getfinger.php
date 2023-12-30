<?php
// BLP 2023-09-27 - This takes the myfingerprints.php file and turns it into json.
// This is for /bartonlp.com/otherpages/getcookie.php file that need to work on HP and Rpi.
// So in getcookie.php we do $me = json_decode(file_get_contents("https://bartonphillips.net/getfinger.php"));
$fingers = require("myfingerprints.php");
echo json_encode($fingers);


