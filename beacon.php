<?php
// BLP 2022-05-27 - Major changes. Add 'visits' and BOTAS_COUNTED logic.
// BLP 2021-06-30 - Moved to bartonphillips.net. No longer using symlinks.

$_site = require_once(getenv("SITELOADNAME"));
$S = new Database($_site);

require_once(SITECLASS_DIR . "/defines.php");

//$DEBUG1 = true; // Set beacon
//$DEBUG2 = true; // real+1 and real+1 bots-1

function isMe($ip) {
  global $S;
  return (array_intersect([$ip], $S->myIp)[0] === null) ? false : true;
}

// The input comes via php as json data not $_GET or $_POST

$data = file_get_contents('php://input');

$data = json_decode($data, true);
$id = $data['id'];
$type = $data['type'];
$site = $data['site'];
$ip = $data['ip'];
$visits = $data['visits'];

if(!$id || $visits === null) {
  error_log("beacon data NO ID or VISITS: site=$site, ip=$ip, type: $type -- \$S->id=$S->ip, \$S->agent=$S->agent");
  echo "<h1>GO AWAY</h1>";
  exit();
}

// Now get botAs and isJavaScrip

$S->query("select botAs, isJavaScript from $S->masterdb.tracker where id=$id");
[$botAs, $java] = $S->fetchrow('num');

// Check if this has been done by tracker.
// NOTE: this will be the case almost all of the time because the client has looked to see if
// beacon is supported and will then always use beacon. I can't really imagin an instance where a
// client could change its mind midway.

if(($java & TRACKER_MASK) == 0) {
  switch($type) {
    case "pagehide":
      $beacon = BEACON_PAGEHIDE;
      break;
    case "unload":
      $beacon = BEACON_UNLOAD;
      break;
    case "beforeunload":
      $beacon = BEACON_BEFOREUNLOAD;
      break;
    case "visibilitychange":
      $beacon = BEACON_VISIBILITYCHANGE;
      break;
    default:
      error_log("beacon ERROR: type=$type, data id=$id, site=$site, ip=$ip, visits=$visits -- \$S->ip=$S->ip, \$S->agent=$S->agent");
      exit();
  }

  // If This was found in the bots table and we are here it probably isn't a bot. If it says
  // 'preg_match' then it probably is a bot so don't remove it.
  
  $bots = 0;

  if($botAs != BOTAS_COUNTED) {
    if($java & TRACKER_BOT && ($botAs == BOTAS_TABLE || strpos($botAs, ',') !== false)) {
      $java &= ~TRACKER_BOT; // Remove BOT if present
      $bots = -1;
    } 
  }

  $java |= $beacon;

  if(!isMe($ip) && $botAs != BOTAS_COUNTED) {
    $S->query("update $S->masterdb.daycounts set `real`=`real`+1, bots=bots+$bots, visits=visits+$visits where date=current_date() and site='$site'");
    if($DEBUG2) error_log("beacon DEBUG2 $type, $id, $site, $ip -- java=" . dechex($java) . ", real+1, bots: $bots, visits: $visits");
  }

  $S->query("update $S->masterdb.tracker set botAs='" . BOTAS_COUNTED . "', endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
            "isJavaScript=$java, lasttime=now() where id=$id");

  if($DEBUG1) error_log("beacon DEBUG1 Set $type, $id, $site, $ip -- visits=$visits, java=" . dechex($java));
} else {
  // There is ways for this to happen:
  // if the client suddenly decides it will support beacon (and I can't imagin
  // how that could happen.
  
  error_log("beacon: Unexpected -- \$date id=$id, site=$site, ip=$ip, type=$type -- \$S->siteName=$S->siteName, \$S->ip=$S->ip");
  echo "<h1>GO AWAY</h1>";
}
