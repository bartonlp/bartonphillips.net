<?php
// BLP 2021-06-30 -- Moved to bartonphillips.net. No longer using symlinks.
// BLP 2014-03-06 -- ajax for tracker.js

$_site = require_once(getenv("SITELOADNAME"));
$S = new Database($_site);

$DEBUG = true; //false; // BLP 2021-06-30 -- for debugging

// BLP 2021-12-24 -- $S now has agent and id
$agent = $S->agent;
$ip = $S->ip;

// BLP 2021-12-24 -- the input comes via php as json data not $_GET or $_POST

$data = file_get_contents('php://input');
$data = json_decode($data, true);
$id = $data['id'];
$w = $data['which'];
$type = $data['type']; // BLP 2021-06-30 -- added here and in tracker.js. This will be 'pagehide', 'unload' or 'beforeunload' from e.type.
$filename = $data['filename'];

if(!$id) {
  $ref = $_SERVER['HTTP_REFERER'] ?? "NONE";
  error_log("beacon: $filename NO ID, ref: $ref, which: $w, ip: $ip, agent: $agent");
  
  echo <<<EOF
<!DOCTYPE html>
<html>
<head>
</head>
<body>
<h1>Go Away!</h1>
</body>
</html>
EOF;

  exit();
} else {
  $S->query("select isJavaScript from $S->masterdb.tracker where id=$id");
  list($js) = $S->fetchrow('num');

  // We want to know if tracker has already updated this record.
  // So if js is not  0x400, 0x200, 0x100 then add the beacon.
  // BLP 2022-01-17 -- 

  // 0x8000 (isMe)
  // 0x4000 (csstest)
  // 0x2000 (bot via SiteClass)
  // 0x1000 (timer)
  // 0x400 (t-pagehide)
  // 0x200 (t-unload)
  // 0x100 (t-beforeunload)
  // 0x80 (b-beforeunload)
  // 0x40 (b-unload)
  // 0x20 (b-pagehide)
  // 0x10 (noscript)
  // 0xf (start,load,script,normal)

  //$mask = (0x8000 | 0x4000 | 0x1000 | 0x800 | 0x400 | 0x200 | 0x10 | 0xf); // should be 0xde1f
  
  if($DEBUG) error_log("beacon: before check $filename -- $ip, js=" . dechex($js) .", type=$type, which=$w");

  // BLP 2022-03-16 -- 
  //if((($js & ~$mask) & 0x700) == 0) { // 0x200 | 0x400 | 0x800

  if(($js & 0x700) == 0) { // not handled by tracker.
    // 'which' can be 1, 2, or 4
    // BLP 2021-06-30 -- 
    
    $beacon = $w * 32; // 0x20, 0x40 or 0x80

    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timediff(now(),starttime), ".
              "isJavaScript=isJavaScript|$beacon, lasttime=now() where id=$id");

  if($DEBUG) error_log("beacon: Set Beacon $filename -- $ip, js= ". dechex($js | $beacon) . ", which=$w, type=$type, agent=$agent");

  }
  exit();
}
