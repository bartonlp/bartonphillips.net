<?php
// BLP 2021-06-30 -- Added $DEBUG etc. No longer using symlinks. This lives at bartonphillips.net
// BLP 2014-03-06 -- ajax for tracker.js
// BLP 2016-12-29 -- NOTE: the $_site info is from a mysitemap.json that is where the tracker.php
// is located (or a directory above it) not necessarily from the mysitemap.json that lives with the
// target program.

$_site = require_once(getenv("SITELOADNAME"));
$S = new Database($_site);

$DEBUG = false; // BLP 2021-06-30 -- added for debugging

// Database (above) does not set $ip or $agent!

$ip = $_SERVER['REMOTE_ADDR'];
$agent = $_SERVER['HTTP_USER_AGENT'];

// Post an ajax error message

if($_POST['page'] == 'ajaxmsg') {
  $msg = $_POST['msg'];
  // NOTE: $_POST['ipagent'] is a string not a boolian! So === true does NOT work but == true
  // or == 'true' does work.
  $ipagent = ($_POST['ipagent'] == 'true') ? ": $ip, $agent" : '';
  error_log("tracker: AJAXMSG, $S->siteName, '$msg'" . $ipagent);
  echo "AJAXMSG OK";
  exit();
}

$S->query("select count(*) from information_schema.tables ".
          "where (table_schema = '$S->masterdb') and (table_name = 'tracker')");

list($ok) = $S->fetchrow('num');

if($ok != 1) {
  error_log("No tracker in $S->masterdb");
  exit();
}

// start is an ajax call

if($_POST['page'] == 'start') {
  $id = $_POST['id'];
  
  if(!$id) {
    error_log("tracker: $S->siteName: START NO ID, $ip, $agent");
    exit();
  }

  // BLP 2021-06-30 -- debug added here and elsewhere.
  
  if($DEBUG) error_log("tracker: start,    $S->siteName, $id, $ip, $agent");
  
  $S->query("update $S->masterdb.tracker set isJavaScript=isJavaScript|1, lasttime=now() where id='$id'");
  echo "Start OK";
  exit();
}

// load is an ajax call from tracker.js

if($_POST['page'] == 'load') {
  $id = $_POST['id'];
  
  if(!$id) {
    error_log("tracker: $S->siteName: LOAD NO ID, $ip, $agent");
    exit();
  }

  if($DEBUG) error_log("tracker: load,    $S->siteName, $id, $ip, $agent");

  $S->query("update $S->masterdb.tracker set isJavaScript=isJavaScript|2, lasttime=now() where id='$id'");
  echo "Load OK";
  exit();
}

// ON EXIT FUNCTIONS
// Page hide is an ajax call from tracker.js

if($_POST['page'] == 'pagehide') {
  $id = $_POST['id'];

  if(!$id) {
    error_log("tracker: $S->siteName: PAGEHIDE NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $S->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  // 0x701f is 0x4000: csstest, 0x2000: robots, 0x101F: 0x1000 timer, 0x10 noscript,
  // 0xf start|load|script|normal
  // So if js is zero after the &~ then we do not have a (32|64|128) beacon,
  // or (256|512) tracker:beforeunload/unload. We should update.

  if($DEBUG) error_log("tracker: pagehide - before check");

  if(($js & ~(0x701f)) == 0) {
    if($DEBUG) error_log("tracker: beforeunload,   $S->siteName, $id, $ip, $agent, $js");
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
              "isJavaScript=isJavaScript|1024, lasttime=now() where id=$id");
    echo "pagehide OK";
  } else {
    echo "js: ".dechex($js)."\n";    
    echo "pagehide Not Done";
  }
  exit();
}

// before unload is an ajax call from tracker.js

if($_POST['page'] == 'beforeunload') {
  $id = $_POST['id'];

  if(!$id) {
    error_log("tracker: $S->siteName: BEFOREUNLOAD NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $S->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  // 0x701f is 0x4000: csstest, 0x2000: robots, 0x101F: 0x1000 timer, 0x10 noscript,
  // 0xf start|load|script|normal
  // So if js is zero after the &~ then we do not have a (32|64|128) beacon,
  // or (256|512) tracker:beforeunload/unload. We should update.

  if($DEBUG) error_log("tracker: beforeunload - before check");
  
  if(($js & ~(0x701f)) == 0) {
    if($DEBUG) error_log("tracker: beforeunload,   $S->siteName, $id, $ip, $agent, $js");
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
              "isJavaScript=isJavaScript|256, lasttime=now() where id=$id");
    echo "beforeunload OK";
  } else {
    echo "js: ".dechex($js)."\n";
    echo "beforeunload Not Done";
  }
  exit();
}

// unload is an ajax call from tracker.js

if($_POST['page'] == 'unload') {
  $id = $_POST['id'];

  if(!$id) {
    error_log("tracker: $S->siteName: UNLOAD NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $S->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  // 0x701f is 0x4000: csstest, 0x2000: robots, 0x101F: 0x1000 timer, 0x10 noscript,
  // 0xf start|load|script|normal
  // So if js is zero after the &~ then we do not have a (32|64|128) beacon,
  // or (256|512) tracker:beforeunload/unload. We should update.

  if($DEBUG) error_log("tracker: unload - before check");
  
  if(($js & ~(0x701f)) == 0) {
    if($DEBUG) error_log("tracker: unload,   $S->siteName, $id, $ip, $agent, $js");
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
              "isJavaScript=isJavaScript|512, lasttime=now() where id=$id");
    echo "Unload OK";
  } else {
    echo "js: ".dechex($js)."\n";
    echo "Unload Not Done";
  }
  exit();
}
// END OF EXIT FUNCTIONS

// START OF IMAGE FUNCTIONS

// BLP 2021-06-30 -- Here is an example of the banner.i.php:
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
// BLP 2021-06-30 -- END

if($_GET['page'] == 'script') {
  $id = $_GET['id'];
  $image = $_GET['image'];
           
  if($DEBUG) error_log("trackerImg1: $image");
  
  if(!$id || $id == 'undefined') {
    error_log("tracker: $S->siteName: SCRIPT NO ID, $ip, $agent");
    exit();
  }

  if($DEBUG) error_log("tracker: script, $S->siteName, $id, $ip, $agent");

  try {
    $sql = "select page, agent from $S->masterdb.tracker where id=$id";
    $S->query($sql);

    list($page, $orgagent) = $S->fetchrow('num');

    $or = 0x4;
    
    if($agent != $orgagent) {
      $sql = "insert into $S->masterdb.tracker (site, ip, page, agent, starttime, refid, isJavaScript, lasttime) ".
             "values('$S->siteName', '$ip', '$page', '$agent', now(), '$id', 0x2004, now())";

      $S->query($sql);
      $or = 0x2004;
    }
  
    $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|$or, lasttime=now() where id=$id";
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  $img1 = "https://bartonphillips.net/images/blank.png"; // default needs full url

  if($image) {
    $pos = strpos($image, "http"); // Look for http at start. It could be http: or https:
    if($pos !== false && $pos == 0) {
      $img1 = $image; // $image has the full url starting with http (could be https)
    } else {
      $img1 = "https://bartonphillips.net" . $image;
    }
  }
  // If no $image then use the default full url.
  
  if($DEBUG) error_log("tracker script image: $img1");
  
  $imageType = preg_replace("~^.*\.(.*)$~", "$1", $img1); // greedy \. so we only see the LAST .
  $img = file_get_contents("$img1");
  header("Content-type: image/$imageType");
  echo $img;
  exit();
}

// We put an image in the banner.i.php that looks like:
// <img src="tracker.php?page=normal&id=$this->LAST_ID"> or something like that.
// This is $image2 from above.
// If this is not there this will never happen!

if($_GET['page'] == 'normal') {
  $id = $_GET['id'];
  $image = $_GET['image'];
  
  if(!$id) {
    error_log("tracker: $S->siteName: NORMAL NO ID, $ip, $agent");
    exit();
  }

  try {
    $sql = "select page, agent from $S->masterdb.tracker where id=$id";
    $S->query($sql);
    list($page, $orgagent) = $S->fetchrow('num');

    $or = 0x8;
    
    if($agent != $orgagent) {
      $sql = "insert into $S->masterdb.tracker (site, ip, page, agent, starttime, refid, isJavaScript, lasttime) ".
             "values('$S->siteName', '$ip', '$page', '$agent', now(), '$id', 0x2008, now())";

      $S->query($sql);
      $or = 0x2008;
    }

    $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|$or, lasttime=now() where id=$id";
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  $img2 = "https://bartonphillips.net/images/blank.png";

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

  if(!$id) {
    error_log("tracker: $S->siteName: NOSCRIPT NO ID, $ip, $agent");
    exit();
  }

  if($DEBUG) error_log("tracker: noscript, $S->siteName, $id, $ip, $agent");

  try {
    $sql = "select page, agent from $S->masterdb.tracker where id=$id";
    $S->query($sql);
    list($page, $orgagent) = $S->fetchrow('num');

    $or = 0x10;
    
    if($agent != $orgagent) {
      $sql = "insert into $S->masterdb.tracker (site, ip, page, agent, starttime, refid, isJavaScript, lasttime) ".
             "values('$S->siteName', '$ip', '$page', '$agent', now(), '$id', 0x2010, now())";

      $S->query($sql);
      $or = 0x2010;
    }

    $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|$or, lasttime=now() where id=$id";
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  $img = file_get_contents("https://bartonphillips.net/images/blank.png");
  header("Content-type: image/png");
  echo $img;
  exit();
}
// END IMAGE FUNCTIONS

// BLP 2021-06-30 -- CSS TEST
// This tests if a css file was ever loaded. We look for 'csstest-(.*)\.css' in our .htaccess file.
// The (.*) is the 'LAST_ID'. tracker.js gets it from the 'script' with the attribute
// 'data-lastid'. tracker.js does:
// lastId = $("script[data-lastid]").attr("data-lastid");
// $("script[data-lastid]").before('<link rel="stylesheet" href="csstest-' + lastId + '.css" title-"blp test">');
// The RewriteRule in .htaccess redirects to 'https://bartonphillips.net/tracker.php?id=$1&csstest'
// $1 is the LAST_ID which was appended to 'csstest-', that is the (.*).

if(isset($_GET['csstest'])) {
  $id = $_GET['id'];

  if(!$id) {
    error_log("tracker: $S->siteName: CSSTEST NO ID, $ip, $agent");
    exit();
  }

  if($DEBUG) error_log("tracker: csstest, $S->siteName, $id, $ip, $agent");

  // For csstest we will set bit 0x4000
  
  try {
    $sql = "select page, agent from $S->masterdb.tracker where id=$id";
    $S->query($sql);
    list($page, $orgagent) = $S->fetchrow('num');

    $or = 0x4000;
    
    if($agent != $orgagent) {
      $sql = "insert into $S->masterdb.tracker (site, ip, page, agent, starttime, refid, isJavaScript, lasttime) ".
             "values('$S->siteName', '$ip', '$page', '$agent', now(), '$id', 0x6000, now())";

      $S->query($sql);
      $or = 0x6000;
    }

    $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|$or, lasttime=now() where id=$id";
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  header("Content-Type: text/css");
  echo "/* csstest.css */";
  exit();
}

// TIMER. This runs while the page is up.

if($_POST['page'] == 'timer') {  
  $id = $_POST['id'];
  $time = $_POST['time'] / 1000;
  $filename = $_POST['filename'];
  
  if($DEBUG) error_log("tracker timer: $S->siteName, $filename, $ip, $time sec.");
  
  if(!$id) {
    error_log("tracker: $S->siteName, $filename: TIMER NO ID, $ip, $agent");
    exit();
  }

  try {
    $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|4096, endtime=now(), ".
           "difftime=timestampdiff(second, starttime, now()), lasttime=now() where id=$id";
    
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  echo "Timer OK";
  exit();
}

// otherwise just go away!

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
