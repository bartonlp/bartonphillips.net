<?php
// Beacon from tracker.js

$_site = require_once(getenv("SITELOADNAME"));
$S = new Database($_site);

require_once(SITECLASS_DIR . "/defines.php");

$DEBUG1 = true; // COUNTED real+1 bots-1
//$DEBUG2 = true; // After update tracker table
//$DEBUG3 = true; // visablechange

// The input comes via php as json data not $_GET or $_POST

$data = file_get_contents('php://input');

$data = json_decode($data, true);
$id = $data['id'];
$type = $data['type'];
$site = $data['site'];
$ip = $data['ip'];
$visits = $data['visits'];
$thepage = $data['thepage'];
$state = $data['state'];

// Here the isMeFalse is passed as a true bool.
$S->isMeFalse = $data['isMeFalse'];

$msg = strtoupper($type);

if(!$id || $visits === null) {
  error_log("beacon:  NO ID, $ip, $site, $msg -- \$S->id=$S->ip, \$S->agent=$S->agent, time=" . (new DateTime)->format('H:i:s:v'));
  echo "<h1>GO AWAY</h1>";
  exit();
}

// Now get botAs and isJavaScrip etc.

$S->query("select botAs, isJavaScript, hex(isJavaScript), difftime, referer, finger, agent from $S->masterdb.tracker where id=$id");
[$botAs, $java, $js, $difftime, $referer, $finger, $agent] = $S->fetchrow('num');

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
      error_log("beacon: $id, $ipdata, $site, $thepage, SWITCH_ERROR_{$type}, botAs=$botAs, java=$js, visits=$visits -- \$S->ip=$S->ip, \$S->agent=$S->agent");
      exit();
  }

  $java |= $beacon;
  $js2 = strtoupper(dechex($java));

  if($ip != $S->ip) {
    error_log("beacon:  $id, $site, $thepage, IP_MISMATCH_{$msg}, \$ip != \$S->ip -- \$S->ip=$S->ip, botAs=$botAs, jsin=$js, jsout=$js2, visits=$visits");
  }

  // Is this a bot?

  if($agent && $S->isBot($agent)) {
    error_log("beacon:  $id, $ip, $site, $thepage, ISABOT_{$msg}1, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, time=" . (new DateTime)->format('H:i:s:v'));
    exit(); // If this is a bot don't bother
  }

  if((str_contains($botAs, BOTAS_COUNTED) === false) && $botAs) { // Has not been counted yet but $botAs has a value.
    // If $botAs has a value other than BOTAS_COUNTED then it must be robot, sitemap, zero or table.
    // I think this is a bot.
    error_log("beacon:  $id, $ip, $site, $thepage, ISABOT_{$msg}2, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, difftime=$difftime, time=" . (new DateTime)->format('H:i:s:v'));
    exit();
  }

  // At this point $botAs can only be blank or have BOTAS_COUNTED
  
  if(!$S->isMyIp($ip) && $botAs != BOTAS_COUNTED) { // it is not ME and it has not been counted yet. If $botAs had anything else it would have been returned above.
    $S->query("select `real`, bots, visits from $S->masterdb.daycounts where date=current_date() and site='$site'");
    [$dayreal, $daybots, $dayvisits] = $S->fetchrow('num');
    $dayreal++;
    $dayvisits += $visits;
    
    $S->query("update $S->masterdb.daycounts set `real`=$dayreal, visits=$dayvisits where date=current_date() and site='$site'");

    if($DEBUG1) error_log("beacon:  $id, $ip, $site, $thepage, COUNTED_{$msg}, real+1, botAs=$botAs, state=$state, jsin=$js, jsout=$js2, real=$dayreal, bots=$daybots, visits: $visits, time=" . (new DateTime)->format('H:i:s:v'));

    $sql = "insert into $S->masterdb.dayrecords (fid, ip, site, page, finger, jsin, jsout, dayreal, daybots, dayvisits, visits, lasttime) ".
           "values($id, '$ip', '$site', '$thepage', '$finger', '$js', '$js2', $dayreal, $daybots, $dayvisits, $visits, now()) ".
           "on duplicate key update finger='$finger', dayreal=$dayreal, daybots=$daybots, dayvisits=$dayvisits, visits=$visits, lasttime=now()";

    $S->query($sql);

    $botAs = BOTAS_COUNTED;
  }

  $S->query("update $S->masterdb.tracker set botAs='$botAs', endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
            "isJavaScript=$java, lasttime=now() where id=$id");

  if(!$S->isMyIp($ip) && $DEBUG2)
    error_log("beacon:  $id, $ip, $site, $thepage, {$msg}2, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, difftime=$difftime, time=" . (new DateTime)->format('H:i:s:v'));

  if(!$S->isMyIp($ip) && $DEBUG3 && $type == 'visibilitychange')
    error_log("beacon:  $id, $ip, $site, $thepage, {$msg}3, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, difftime=$difftime, time=" . (new DateTime)->format('H:i:s:v'));
} else {
  // There is ways for this to happen:
  // If the client suddenly decides it will support beacon (and I can't imagin
  // how that could happen.
  // If the this is called directly.
  
  error_log("beacon: Unexpected -- \$date $id,$ip, $site, $thepage, java=$js, type=$type -- \$S->siteName=$S->siteName, \$S->ip=$S->ip, time=" . (new DateTime)->format('H:i:s:v'));
  echo "<h1>GO AWAY</h1>";
}
