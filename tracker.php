<?php
// Track the various thing that happen. Some of this is done via JavaScript while others are by the
// header images and the csstest that is in the .htaccess file as a RewirteRule.
// NOTE: the $_site info is from a mysitemap.json that is where the tracker.php
// is located (or a directory above it) not necessarily from the mysitemap.json that lives with the
// target program.

$_site = require_once(getenv("SITELOADNAME")); // mysitemap.json has count false.
$S = new Database($_site);

//error_log("tracker \$S: " . print_r($S, true));

require_once(SITECLASS_DIR . "/defines.php"); // constants for TRACKER, BOTS, BEACON.

//$DEBUG1 = true; // AJAX: start, load
//$DEBUG2 = true; // AJAX: pagehide, beforeunload, unload
//$DEBUG3 = true; // AJAX: 'not done' pagehide, beforeunload, unload
//$DEBUG4 = true; // GET: script, normal, noscript
//$DEBUG5 = true; // JavaScript: timer
//$DEBUG6 = true; // RewriteRule: csstest
//$DEBUG7 = true; // show real+1 and real+1 bots-1
//$DEBUG10 = true; // ref info
$DEBUG11 = true; // start and loat with visits

function isMe($ip) {
  global $S;
//  return false;
  return (array_intersect([$ip], $S->myIp)[0] === null) ? false : true;
}

// ****************************************************************
// All of the following are the result of a javascript interactionl
// ****************************************************************

// Post an ajax error message

if($_POST['page'] == 'ajaxmsg') {
  $msg = $_POST['msg'];
  $ipagent = $_POST['ipagent'];
  
  error_log("tracker AJAXMSG $S->siteName: '$msg' " . $ipagent);
  echo "AJAXMSG OK";
  exit();
}

// start is an ajax call from tracker.js

if($_POST['page'] == 'start') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip']; // This is the real ip of the program. $S->ip will be the ip of ME.
  $visits = $_POST['visits']; // Visits may be 1 or zero. tracker.js sets the mytime cookie.

  if(!$id) {
    error_log("tracker $site, $ip: START NO ID");
    exit();
  }

  if(isMe($ip)) {
    $visits = 0;
  }
  
  $S->query("select botAs, isJavaScript from $S->masterdb.tracker where id=$id");
  [$botAs, $java] = $S->fetchrow('num');

  //error_log("start: botAs=$botAs, java=" . dechex($java));
  
  $bots = 0;
  
  if($botAs != BOTAS_COUNTED) {
    if($java & TRACKER_BOT && ($botAs == BOTAS_TABLE || strpos($botAs, ',') !== false)) {
      $java &= ~TRACKER_BOT; // Remove BOT if present
      $bots = -1;
    } 
  }

  $java |= TRACKER_START; 

  if(!isMe($ip) && $botAs != BOTAS_COUNTED) {
    $S->query("update $S->masterdb.daycounts set `real`=`real`+1, bots=bots+$bots, visits=visits+$visits where date=current_date() and site='$site'");
    if($DEBUG7 || $DEBUG11) error_log("tracker DEBUG7/11 start, $id, $site, $ip -- java=" . dechex($java) . ", real+1, bots: $bots, visits: $visits");
  }

  $S->query("update $S->masterdb.tracker set botAs='" . BOTAS_COUNTED . "' where id=$id");

  if($DEBUG1) error_log("tracker DEBUG1 start, $id, $site, $ip -- visits=$visits, java=" . dechex($java));
  
  $S->query("update $S->masterdb.tracker set isJavaScript=$java, lasttime=now() where id='$id'");
  echo "Start OK, bots: $bots, visits: $visits, java=" . dechex($java);
  exit();
}

// load is an ajax call from tracker.js

if($_POST['page'] == 'load') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $visits = $_POST['visits'];
        
  if(!$id) {
    error_log("tracker $site, $ip: LOAD NO ID");
    exit();
  }

  $S->query("select botAs, isJavaScript from $S->masterdb.tracker where id=$id");
  [$botAs, $java] = $S->fetchrow('num');

  //error_log("load: botAs=$botAs, java=" . dechex($java));

  $bots = 0;
  
  if($botAs != BOTAS_COUNTED) {
    if($java & TRACKER_BOT && ($botAs == BOTAS_TABLE || strpos($botAs, ',') !== false)) {
      $java &= ~TRACKER_BOT; // Remove BOT if present
      $bots = -1;
    } 
  }

  $java |= TRACKER_LOAD;
  
  if(!isMe($ip) && $botAs != BOTAS_COUNTED) {
    $S->query("update $S->masterdb.daycounts set `real`=`real`+1, bots=bots+$bots, visits=visits+$visits where date=current_date() and site='$site'");
    if($DEBUG7 || $DEBUG11) error_log("tracker DEBUG7/11 load, $id, $site, $ip -- java=" . dechex($java) . ", real+1, bots: $bots, visits: $visits");
  }

  $S->query("update $S->masterdb.tracker set botAs='" . BOTAS_COUNTED . "' where id=$id");

  if($DEBUG1) error_log("tracker DEBUG1 load, $id, $site, $ip -- visits: $visits, java=" . dechex($java));

  $S->query("update $S->masterdb.tracker set isJavaScript=$java, lasttime=now() where id='$id'");
  echo "Load OK, bots: $bots, visits: $visits, java=" . dechex($java);
  exit();
}

// ON EXIT FUNCTIONS
// NOTE: There will be very few clients that do not support beacon. Only very old versions of
// browsers and of course MS-Ie. Therefore these should not happen often.

// Page hide is an ajax call from tracker.js

if($_POST['page'] == 'pagehide') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $visits = $_POST['visits'];
  
  if(!$id) {
    error_log("tracker $site, $ip: PAGEHIDE NO ID");
    exit();
  }

  $S->query("select botAs, isJavaScript from $S->masterdb.tracker where id=$id");
  [$botAs, $java] = $S->fetchrow('num');

  // NOTE: this check is really not necessary because if the client's browser supports beacon it is
  // unlikey (really imposible) that the browser would change its mind.
  
  if(($java & BEACON_MASK) == 0) { // Not handled by BEACON
    $bots = 0;

    if($botAs != BOTAS_COUNTED) {
      if($java & TRACKER_BOT && ($botAs == BOTAS_TABLE || strpos($botAs, ',') !== false)) {
        $java &= ~TRACKER_BOT; // Remove BOT if present
        $bots = -1;
      } 
    }

    $java |= TRACKER_PAGEHIDE;

    if(!isMe($ip) && $botAs != BOTAS_COUNTED) {
      $S->query("update $S->masterdb.daycounts set `real`=`real`+1, bots=bots+$bots, visits=visits+$visits where date=current_date() and site='$site'");
      if($DEBUG7) error_log("tracker DEBUG7 pagehide, $id, $site, $ip -- java=" . dechex($java) . ", real+1, bots: $bots, visits: $visits");
    }
    $S->query("update $S->masterdb.tracker set botAs='" . BOTAS_COUNTED . "' where id=$id");

    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
              "isJavaScript=$java, lasttime=now() where id=$id");

    if($DEBUG2) error_log("tracker DEBUG2 Set pagehide, $id, $site, $ip -- visits: $visits, java=" . dechex($java));

    echo "Pagehide OK, bots: $bots, visits: $visits, java=" . dechex($java);
  } else {
    // This will only happen if the client somehow stops supporting beacon (and I can't imagin how
    // that could happen.
    
    if($DEBUG3) error_log("tracker DEBUG3 pagehide Not Done $id, $site, $ip -- visits: $visits, java=" . dechex($java));
    echo "js: ".dechex($js)."\n";    
    echo "tracker, pagehide Not Done";
  }
  exit();
}

// before unload is an ajax call from tracker.js

if($_POST['page'] == 'beforeunload') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $visits = $_POST['visits'];
  
  if(!$id) {
    error_log("tracker $site, $ip: BEFOREUNLOAD NO ID");
    exit();
  }

  $S->query("select botAs, isJavaScript from $S->masterdb.tracker where id=$id");
  [$botAs, $java] = $S->fetchrow('num');

  if(($java & BEACON_MASK) == 0 ) {
    $bots = 0;

    if($botAs != BOTAS_COUNTED) {
      if($java & TRACKER_BOT && ($botAs == BOTAS_TABLE || strpos($botAs, ',') !== false)) {
        $java &= ~TRACKER_BOT; // Remove BOT if present
        $bots = -1;
      } 
    }

    $java |= TRACKER_BEFOREUNLOAD;

    if(!isMe($ip) && $botAs != BOTAS_COUNTED) {
      $S->query("update $S->masterdb.daycounts set `real`=`real`+1, bots=bots+$bots, visits=visits+$visits where date=current_date() and site='$site'");
      if($DEBUG7) error_log("tracker DEBUG7 beforeunload, $id, $site, $ip -- java=" . dechex($java) . ", real+1, bots: $bots, visits: $visits");
    }
    $S->query("update $S->masterdb.tracker set botAs='" . BOTAS_COUNTED . "' where id=$id");
    
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
              "isJavaScript=$java, lasttime=now() where id=$id");

    if($DEBUG2) error_log("tracker DEBUG2 Set beforeunload, $id, $site, $ip -- visits: $visits, java=" . dechex($java));

    echo "Beforeunload OK, bots: $bots, visits: $visits, java=" . dechex($java);
  } else {
    if($DEBUG3) error_log("tracker DEBUG3 beforeunload Not Done $id, $site, $ip -- visits: $visits, java=" . dechex($java));    
    echo "tracker, beforeunload Not Done";
  }
  exit();
}

// unload is an ajax call from tracker.js

if($_POST['page'] == 'unload') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $visits = $_POST['visits'];
  
  if(!$id) {
    error_log("tracker $site, $ip: UNLOAD NO ID");
    exit();
  }
  
  $S->query("select botAs, isJavaScript from $S->masterdb.tracker where id=$id");
  [$botAs , $java] = $S->fetchrow('num');
  
  if(($java & BEACON_MASK) == 0) { // NOT handled by beacon
    $bots = 0;

    if($botAs != BOTAS_COUNTED) {
      if($java & TRACKER_BOT && ($botAs == BOTAS_TABLE || strpos($botAs, ',') !== false)) {
        $java &= ~TRACKER_BOT; // Remove BOT if present
        $bots = -1;
      } 
    }

    $java |= TRACKER_UNLOAD;

    if(!isMe($ip) && $botAs != BOTAS_COUNTED) {
      $S->query("update $S->masterdb.daycounts set `real`=`real`+1, bots=bots+$bots, visits=visits+$visits where date=current_date() and site='$site'");
      if($DEBUG7) error_log("tracker DEBUG7 unload, $id, $site, $ip -- java=" . dechex($java) . ", real+1, bots: $bots, visits: $visits");
    }
    $S->query("update $S->masterdb.tracker set botAs='" . BOTAS_COUNTED . "' where id=$id");

    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
              "isJavaScript=$java, lasttime=now() where id=$id");

    if($DEBUG2) error_log("tracker DEBUG2 Set unload, $id, $site, $ip -- visits: $visits, java=" . dechex($java));
    
    echo "Unload OK, bots: $bots, visits: $visits, java=" . dechex($java);
  } else {
    if($DEBUG3) error_log("tracker DEBUG3 unload Not Done $id, $site, $ip -- visits: $visits, java=" . dechex($java));    
    echo "tracker, Unload Not Done";
  }
  exit();
}
// END OF EXIT FUNCTIONS

// timer is an ajax call from tracker.js
// TIMER. This runs while the page is up.

if($_POST['page'] == 'timer') {  
  $id = $_POST['id'];
  $time = $_POST['time'] / 1000;
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $visits = $_POST['visits'];
  
  if(!$id) {
    error_log("tracker $site, $ip: TIMER NO ID");
    exit();
  }

  // If we have a TIMER then this is probably NOT a bot. So remove BOT if it's there.
  
  $S->query("select botAs, isJavaScript from $S->masterdb.tracker where id=$id");
  [$botAs, $java] = $S->fetchrow('num');

  $bots = 0;
  
  if($botAs != BOTAS_COUNTED) {
    if($java & TRACKER_BOT && ($botAs == BOTAS_TABLE || strpos($botAs, ',') !== false)) {
      $java &= ~TRACKER_BOT; // Remove BOT if present
      $bots = -1;
    } 
  }

  $java |= TRACKER_TIMER; // Or in TIMER

  if(!isMe($ip) && $botAs != BOTAS_COUNTED) {
    $S->query("update $S->masterdb.daycounts set `real`=`real`+1, bots=bots+$bots, visits=visits+$visits where date=current_date() and site='$site'");
    if($DEBUG5) error_log("tracker DEBUG5 timer, $id, $site, $ip -- java=" . dechex($java) . ", real+1, bots: $bots, visits: $visits");
  }
  $S->query("update $S->masterdb.tracker set botAs='" . BOTAS_COUNTED . "' where id=$id");
  
  if($DEBUG5) error_log("tracker DEBUG5 timer: $id, $site, $ip -- visits: $visits, java=" . dechex($java));
  
  $sql = "update $S->masterdb.tracker set isJavaScript=$java, endtime=now(), ".
         "difftime=timestampdiff(second, starttime, now()), lasttime=now() where id=$id";
    
  $S->query($sql);
  
  echo "Timer OK, bots: $bots, visits: $visits, java=" . dechex($java);
  exit();
}

// *********************************************
// This is the END of the javascript AJAX calls.
// *********************************************

// START OF IMAGE FUNCTIONS. These are NOT javascript but rather use $_GET.
// NOTE: The image functions are GET calls from the original php file. These are not done by
// tracker.js!

// Here is an example of the banner.i.php:
// <header>
//   <a href="https://www.bartonphillips.com">
//    <img id='logo' data-image="image" src="https://bartonphillips.net/images/blp-image.png"></a>
// $image2
// $mainTitle
// <noscript>
// <p style='color: red; background-color: #FFE4E1; padding: 10px'>
// $image3
// Your browser either does not support <b>JavaScripts</b> or you have JavaScripts disabled, in either case your browsing
// experience will be significantly impaired. If your browser supports JavaScripts but you have it disabled consider enabaling
// JavaScripts conditionally if your browser supports that. Sorry for the inconvienence.</p>
// </noscript>
// </header>
//
// tracker.js changes the <img id='logo' ... from the above to 'src' attribute:
// src="https://bartonphillips.net/tracker.php?page=script&id="+lastId+"&image="+image);
// When tracker.php is called to get the image 'page' has the values script, normal or noscript.

$ref = $_SERVER['HTTP_REFERER']; // Get the referer

if($_GET['page'] == 'script') {
  $id = $_GET['id'];
  $image = $_GET['image'];

  if($S->isBot) {
    error_log("tracker script, $id, $S->siteName, $S->ip -- image=$image, THIS IS A BOT"); 
    return; // If this is a bot don't bother
  }
  
  if(!$id) {
    error_log("tracker script: NO ID, $S->ip, $S->agent");
    exit();
  }

  $S->query("select site from $S->masterdb.tracker where id=$id");
  $site = $S->fetchrow('num')[0];
  
  if($DEBUG10) error_log("tracker DEBUG10 script $id, $site, $S->ip -- referer=$ref");
  
  $or = TRACKER_SCRIPT;
  
  $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|$or, lasttime=now() where id=$id";
  $S->query($sql);

  $img1 = "https://bartonphillips.net/images/blank.png"; // default needs full url

  if($DEBUG4) error_log("tracker DEBUG4 script $id, $site, $S->ip -- trackerImg1: $image, default: $img1");

  if($image) {
    $pos = strpos($image, "http"); // Look for http at start. It could be http: or https:
    if($pos !== false && $pos == 0) {
      $img1 = $image; // $image has the full url starting with http (could be https)
    } else {
      $img1 = "https://bartonphillips.net" . $image;
    }
  }
  // If no $image then use the default full url.

  $imageType = preg_replace("~^.*\.(.*)$~", "$1", $img1); // greedy \. so we only see the LAST .
  $img = file_get_contents("$img1");
  header("Content-type: image/$imageType");
  echo $img;
  exit();
}

// We put an image in the banner.i.php that looks like:
// <img src="tracker.php?page=normal&id=$this->LAST_ID&image=$this->trackerImg2"> or something like that.
// This is $image2 from above.
// If this is not there this will never happen!

if($_GET['page'] == 'normal') {
  $id = $_GET['id'];
  $image = $_GET['image'];

  if($S->isBot) {
    error_log("tracker normal, $id, $S->siteName, $S->ip -- image=$image, THIS IS A BOT"); 
    return; // If this is a bot don't bother
  }
  
  if(!$id) {
    error_log("tracker normal: NO ID, $S->ip, $S->agent");
    exit();
  }

  $S->query("select site from $S->masterdb.tracker where id=$id");
  $site = $S->fetchrow('num')[0];
  
  if($DEBUG10) error_log("tracker DEBUG10 normal $id, $site, $S->ip -- referer=$ref");
  
  $or = TRACKER_NORMAL;
  $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|$or, lasttime=now() where id=$id";
  $S->query($sql);
  
  $img2 = "https://bartonphillips.net/images/blank.png";

  if($DEBUG4) error_log("tracker DEBUG4 normal $id, $site, $S->ip -- trackerImg2: $image, default: $img2");

  if($image) {
    $pos = strpos($image, "http");
    if($pos !== false && $pos == 0) {
      $img2 = $image; // $image has the full url starting with http (could be https)
    } else {
      $img2 = "https://bartonphillips.net" . $image;
    }
  }

  $imageType = preg_replace("~.*\.(.*)$~", "$1", $img2);
  
  $img = file_get_contents("$img2");
  header("Content-type: image/$imageType");
  echo $img;
  exit();
}

// Via the <img> in the 'noscript' tag in the banner.i.php. See comment BLP 2021-06-30 -- This is
// $image3 from above.

if($_GET['page'] == 'noscript') {
  $id = $_GET['id'];

  if($S->isBot) {
    error_log("tracker noscript, $id, $S->siteName, $S->ip -- image=$image, THIS IS A BOT"); 
    return; // If this is a bot don't bother
  }
  
  if(!$id) {
    error_log("tracker noscript: NO ID, $ip, $S->agent");
    exit();
  }

  $S->query("select site from $S->masterdb.tracker where id=$id");
  $site = $S->fetchrow('num')[0];
  
  if($DEBUG10) error_log("tracker DEBUG10 noscript $id, $site, $S->ip -- referer=$ref");

  $or = TRACKER_NOSCRIPT;
  $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|$or, lasttime=now() where id=$id";
  $S->query($sql);
  
  $img = file_get_contents("https://bartonphillips.net/images/blank.png");

  if($DEBUG4) error_log("tracker DEBUG4 noscript $id, $site, $S->ip -- default: $img");

  header("Content-type: image/png");
  echo $img;
  exit();
}
// END IMAGE FUNCTIONS

// BLP 2021-06-30 -- CSS TEST
// Tests if a css file was ever loaded. We look for 'csstest-(.*)\.css' in our .htaccess file.
// The (.*) is the 'LAST_ID'. tracker.js gets it from the 'script' with the attribute
// 'data-lastid'. tracker.js does:
// lastId = $("script[data-lastid]").attr("data-lastid");
// $("script[data-lastid]").before('<link rel="stylesheet" href="csstest-' + lastId + '.css" title-"blp test">');
// The RewriteRule in .htaccess redirects to 'https://bartonphillips.net/tracker.php?id=$1&csstest'
// $1 is the LAST_ID which was appended to 'csstest-', that is the (.*).
// NOTE: this is called from .htaccess not from tracker.js.
// Also NOTE: $_GET['csstest'] is set even though no value is assigned to 'csstest'.

if(isset($_GET['csstest'])) {
  $id = $_GET['id'];

  if($S->isBot) {
    error_log("tracker csstest, $id, $S->siteName, $S->ip -- THIS IS A BOT"); 
    return; // If this is a bot don't bother
  }
  
  if(!$id) {
    // BLP 2021-12-24 -- If there is no javascript running and .htaccess tries to load csstest it
    // will have no LAST_ID.
    error_log("tracker csstest: NO ID, $ip, $S->agent");
    exit();
  }

  $S->query("select site from $S->masterdb.tracker where id=$id");
  $site = $S->fetchrow('num')[0];
  
  if($DEBUG10) error_log("tracker DEBUG10 csstest $id, $site, $S->ip -- referer=$ref");

  // For csstest we will set bit 0x4000
  
  $or = TRACKER_CSS;
  $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|$or, lasttime=now() where id=$id";
  $S->query($sql);

  if($DEBUG6) error_log("tracker DEBUG6 csstest: $id, $site, $S->ip");
  
  header("Content-Type: text/css");
  echo "/* csstest.css */";
  exit();
}

// This is a mystry to me. How someone tried to use tracker.

$id = $_GET['id'] ?? $_POST['id'];

if($id) {
  // If this ID is not in the table add it with TRACKER_GOAWAY.
  
  $S->query("insert into $S->masterdb.tracker (id, site, ip, agent, isJavaScript, lasttime) ".
            "values($id, '$S->siteName', '$S->ip', '$S->agent', " . TRACKER_GOAWAY . ", now()) ".
            "on duplicate key update isJavaScript=isJavaScript|" . TRACKER_GOAWAY . ", lasttime=now()");
}

$id = $id ? ", id=$id" : '';

// otherwise just go away!

$sql = "select finger from tracker where ip='$S->ip'";
$S->query($sql);
$finger = $S->fetchrow('num')[0] ?? "NONE";
$request = $_REQUEST ? ", \$_REQUEST: " . print_r($_REQUEST, true) : '';

error_log("tracker GOAWAY $S->ip, $S->agent, finger: $finger{$id}{$request}");

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
