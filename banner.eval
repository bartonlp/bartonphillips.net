<?php
// BLP 2021-06-30 -- Moved to bartonphillips.net. No longer using symlinks.
// BLP 2014-03-06 -- ajax for tracker.js

$_site = require_once(getenv("SITELOADNAME"));
$S = new Database($_site);

$DEBUG = false; // BLP 2021-06-30 -- for debugging

// Database() does not set $agent or $ip!
$agent = $_SERVER['HTTP_USER_AGENT'];
$ip = $_SERVER['REMOTE_ADDR'];

$data = file_get_contents('php://input');
$data = json_decode($data, true);
$id = $data['id'];
$w = $data['which'];
$type = $data['type']; // BLP 2021-06-30 -- added here and in tracker.js

if(!$id) {
  error_log("beacon: $S->siteName: NO ID, which: $w, ip: $ip, agent: $agent");
  
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

  // We want to know if tracker or beacon has already updated this record.
  // 4127 is 0x101F or 0x1000 timer, 0x10 noscript, 0xf start|load|script|normal
  // So if js is zero after the &~ then we do not have a 0x20, 0x40, 0x80 (32|64|128) beacon,
  // or 0x100, 0x200, 0x400 (256|512|1024) tracker:pagehide/beforeunload/unload. We should update.

  // BLP 2021-06-30 --
  
  if($DEBUG) error_log("beacon: before check -- which=$w, type=$type,  $S->siteName");
  
  if(($js & ~(4127)) == 0) {
    // 'which' can be 1, 2, or 4
    // BLP 2021-06-30 -- 
    if($DEBUG) error_log("beacon: which=$w, type=$type, $S->siteName, $id, $ip, $agent");
    
    $beacon = $w * 32; // 0x20, 0x40 or 0x80
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timediff(now(),starttime), ".
              "isJavaScript=isJavaScript|$beacon, lasttime=now() where id=$id");
  }
  exit();
}
