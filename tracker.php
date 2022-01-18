<?php
// BLP 2021-12-15 -- cosmetic changes to error_log messages etc.
// BLP 2021-06-30 -- Added $DEBUG etc. No longer using symlinks. This lives at bartonphillips.net
// BLP 2014-03-06 -- ajax for tracker.js
// BLP 2016-12-29 -- NOTE: the $_site info is from a mysitemap.json that is where the tracker.php
// is located (or a directory above it) not necessarily from the mysitemap.json that lives with the
// target program.

$_site = require_once(getenv("SITELOADNAME"));
$S = new Database($_site);

//$DEBUG1 = true;  // BLP 2022-01-17 -- AJAX posts
//$DEBUG2 = true; // BLP 2022-01-17 -- GET
//$DEBUG3 = true; // BLP 2022-01-17 -- Timer

// BLP 2021-10-25 -- I have fixed Database to now have ip and agent.

$ip = $S->ip;
$agent = $S->agent;

// Post an ajax error message

if($_POST['page'] == 'ajaxmsg') {
  $msg = $_POST['msg'];
  // NOTE: $_POST['ipagent'] is a string not a boolian! So === true does NOT work but == true
  // or == 'true' does work.
  $ipagent = ($_POST['ipagent'] == 'true') ? ": $ip, $agent" : '';
  error_log("tracker AJAXMSG: $S->siteName, '$msg'" . $ipagent);
  echo "AJAXMSG OK";
  exit();
}

$S->query("select count(*) from information_schema.tables ".
          "where (table_schema = '$S->masterdb') and (table_name = 'tracker')");

list($ok) = $S->fetchrow('num');

if($ok != 1) {
  error_log("tracker: No tracker in $S->masterdb");
  exit();
}

// start is an ajax call from tracker.js

if($_POST['page'] == 'start') {
  $id = $_POST['id'];
  $filename = $_POST['filename'];
  
  if(!$id) {
    error_log("tracker: $filename: START NO ID, $ip, $agent");
    exit();
  }

  // BLP 2021-06-30 -- debug added here and elsewhere.
  
  if($DEBUG1) error_log("tracker: start, $filename, $id, $ip, $agent");
  
  $S->query("update $S->masterdb.tracker set isJavaScript=isJavaScript|1, lasttime=now() where id='$id'");
  echo "Start OK";
  exit();
}

// load is an ajax call from tracker.js

if($_POST['page'] == 'load') {
  $id = $_POST['id'];
  $filename = $_POST['filename'];
  
  if(!$id) {
    error_log("tracker: $filename LOAD NO ID, $ip, $agent");
    exit();
  }

  if($DEBUG1) error_log("tracker: load, $filename, $id, $ip, $agent");

  $S->query("update $S->masterdb.tracker set isJavaScript=isJavaScript|2, lasttime=now() where id='$id'");
  echo "Load OK";
  exit();
}

// ON EXIT FUNCTIONS
// Page hide is an ajax call from tracker.js

if($_POST['page'] == 'pagehide') {
  $id = $_POST['id'];
  $filename = $_POST['filename'];
  
  if(!$id) {
    error_log("tracker: $filename PAGEHIDE NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $S->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  $mask = (0x8000 | 0x4000 | 0x1000 | 0x1000 | 0x80 | 0x40 | 0x20 | 0x10 | 0xf); // should be 0xd0ff
  
  if($DEBUG1) error_log("tracker: before check $filename -- $ip, js=" . dechex($js) . ", type=pagehide");

  if((($js & ~$mask) & 0xe0) == 0) {
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
              "isJavaScript=isJavaScript|0x400, lasttime=now() where id=$id");
    if($DEBUG1) error_log("tracker: Set tracker $filename -- $ip, js=" . dechex($js | 0x400) . ", pagehide, id=$id, $agent");

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
  $filename = $_POST['filename'];
  
  if(!$id) {
    error_log("tracker: $filename BEFOREUNLOAD NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $S->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  $mask = (0x8000 | 0x4000 | 0x1000 | 0x1000 | 0x80 | 0x40 | 0x20 | 0x10 | 0xf); // should be 0xd0ff
  
  if($DEBUG1) error_log("tracker: before check $filename -- $ip, js=" . dechex($js) . ", type=beforeunload");
  
  if((($js & ~ $mask) & 0xe0) == 0 ) {
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
              "isJavaScript=isJavaScript|0x100, lasttime=now() where id=$id");
    if($DEBUG1) error_log("tracker: Set tracker $filename -- $ip, js=" . dechex($js | 0x100) . ", beforeunload, id=$id, $agent");

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
  $filename = $_POST['filename'];
  
  if(!$id) {
    error_log("tracker: $filename UNLOAD NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $S->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  $mask = (0x8000 | 0x4000 | 0x1000 | 0x1000 | 0x80 | 0x40 | 0x20 | 0x10 | 0xf); // should be 0xd0ff

  if($DEBUG1) error_log("tracker: before check $filename -- $ip, js=" . dechex($js) . ", type=unload");
  
  if((($js & ~$mask) & 0xe0) == 0) {
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
              "isJavaScript=isJavaScript|0x200, lasttime=now() where id=$id");

    if($DEBUG1) error_log("tracker: Set tracker $filename -- $ip, js=" . dechex($js | 0x200) . ", beforeunload, id=$id, $agent");
    
    echo "Unload OK";
  } else {
    echo "js: ".dechex($js)."\n";
    echo "Unload Not Done";
  }
  exit();
}
// END OF EXIT FUNCTIONS

// timer is an ajax call from tracker.js
// TIMER. This runs while the page is up.

if($_POST['page'] == 'timer') {  
  $id = $_POST['id'];
  $time = $_POST['time'] / 1000;
  $filename = $_POST['filename'];
  
  if($DEBUG3) error_log("tracker timer: $filename, $ip, $time sec.");
  
  if(!$id) {
    error_log("tracker: $filename TIMER NO ID, $ip, $agent");
    exit();
  }

  try {
    $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|0x1000, endtime=now(), ".
           "difftime=timestampdiff(second, starttime, now()), lasttime=now() where id=$id";
    
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  echo "Timer OK";
  exit();
}

// START OF IMAGE FUNCTIONS
// NOTE: The image functions are GET calls from the original php file. These are not done by
// tracker.js!

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
           
  if($DEBUG2) error_log("tracker script: trackerImg1: $image");
  
  if(!$id || $id == 'undefined') {
    error_log("tracker script: NO ID, $ip, $agent");
    exit();
  }

  if($DEBUG2) error_log("tracker script: $id, $ip, $agent");

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
  
  if($DEBUG2) error_log("tracker script: default-image: $img1");
  
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
  
  if(!$id) {
    error_log("tracker normal: NO ID, $ip, $agent");
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
    error_log("tracker noscript: NO ID, $ip, $agent");
    exit();
  }

  if($DEBUG2) error_log("tracker noscript: $id, $ip, $agent");

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

  if(!$id) {
    // BLP 2021-12-24 -- If there is no javascript running and .htaccess tries to load csstest it
    // will have no LAST_ID.
    error_log("tracker csstest: NO ID, $ip, $agent");
    exit();
  }

  if($DEBUG2) error_log("tracker csstest: $id, $ip, $agent");

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

// otherwise just go away!

$sql = "select finger from tracker where ip='$ip'";
$S->query($sql);
$finger = $S->fetchrow('num')[0] ?? "NONE";

error_log("tracker GOAWAY: ip: $ip, finger: $finger, agent: $agent");

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
