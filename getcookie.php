<?php
// Reset Cookie. Show the member, myip and geo tables. Show a map of geo lat/long.
// This file needs to be symlinked into the local directories.
// BLP 2021-10-26 -- add 'form' and POST logic.
// BLP 2021-10-22 -- Added Google maps:
// To Remotely debug from my Tablet:
// In the desktop browser: chrome://inspect/#devices
// Plug the tablet into the USB port. You should see "Chrome" and each of the domains that are open on the tablet.
// You can then debug the tablet if you click on "inspect". It will open the dev-tools.
// BLP 2021-09-23 -- Created

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);
$S = new $_site->className($_site);

$DEBUG = false; // set to true to debug

if($_GET['blp'] == "8653") {
  $DEBUG = true;
}

// BLP 2021-10-26 -- From the 'form' with the siteNames.

if(isset($_POST['submit'])) {
  $siteName = $_POST['site'];

  // use header() to go to the loocation.
  
  switch($siteName) {
    case 'Bartonphillips':
      header("Location: https://www.bartonphillips.com/getcookie.php");
      break;
    case 'Tysonweb':
      header("Location: https://www.newbern-nc.info/getcookie.php");
      break;
    case 'Newbernzig':
      header("Location: http://www.newbernzig.com/getcookie.php");
      break;
    default:
      echo "OPS something went wrong: siteName: $siteName";
  }
  exit();
} 

// Look to see if this ip in in myIp array.

function isMe($S) {
  return (array_intersect([$S->ip], $S->myIp)[0] !== null) ? true : false;
}

$h->banner = "<h1>Reset Cookie</h1>";
// Uncomment this to see an analysis of the head section.
//$h->link = "<link rel='stylesheet' href='https://csswizardry.com/ct/ct.css' class='ct' />";

// I could change the scale
//$h->meta = '<meta name=viewport content="width=device-width initial-scale=.7">';

$h->css =<<<EOF
<style>
#members {
  margin: 10px 0;
}
#myip {
  margin: 10px 0;
}
.myhome {
  color: white;
  background: green;
  padding: 0 5px;
}
.less-margin {
  margin-top: -1.2em;
}
.reset {
  border: 1px solid black;
  border-radius: 5px;
  color: white;
  background: green;
  width: 10em;
  text-align: left;
  margin-right: 10px;
}
#resetmsg {
  list-style-type: none;
}
/* mygeo is the table */
#myip td, #members td , #mygeo td {
  padding: 2px 5px;
}
#mygeo td {
  cursor: pointer;
}
#mygeo th {
  width: 220px;
}
/* geo is the div for the google maps image */
#geocontainer {
  width: 500px;
  height: 500px;
  margin-left: auto;
  margin-right: auto;
  border: 5px solid black;
  z-index: 20;
}
#contain {
  grid-area: main;
}
#showMe, #showAll {
  border-radius: 5px;
  color: white;
  background: green;
  padding: 2px;
}
#removemsg {
  display: none;
  color: white;
  background: red;
  border-radius: 5px;
  padding: 2px;
}
#outer {
  display: none;
  position: absolute;
  z-index: 20;
  padding-bottom: 30px;
}
@media (max-width: 850px) {
  html { font-size: 10px; }
  #geocontainer {
    width: 360px;
    height: 360px;
  }
}
@media (hover: none) and (pointer: coarse) {
  html { font-size: 10px; }
  #resetmsg { padding-inline-start: 5px; }
  #members td { width: 50px; };  
  /* mygeo is the table */
  #mygeo td {
    padding: 2px 2px 2px 5px;
    width: 50px;
  }
  /* geo is the div for the image */
  #geocontainer {
    width: 360px;
    height: 360px;
  }
}
</style>
EOF;

// This goes after footer
// Get the google maps api key form a secure location.
// BLP 2021-11-06 -- This key is further restricted to my domains only. See
// https://console.cloud.google.com/google/maps-apis/overview?project=barton-1324
// Therefore even though this file is on GitHub and the key is being leaked to the public it can
// only be used from one of my domains!

$APIKEY = require_once("/var/www/bartonphillipsnet/PASSWORDS/maps-apikey");

$b->script = <<<EOF
<script src="https://bartonphillips.net/js/maps.js"></script>
<script
 src="https://maps.googleapis.com/maps/api/js?key=$APIKEY&callback=initMap&v=weekly" async>
</script>
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

// Get the two tables

$T = new dbTables($S);

$sql = "select name, email, ip, agent, created, lasttime from bartonphillips.members";

[$members] = $T->maketable($sql, array('attr'=>array('border'=>'1', 'id'=>'members')));

$sql = "select myIp, createtime, lasttime from $S->masterdb.myip";

// Get my home IP

$home = gethostbyname("bartonphillips.dyndns.org");

// callback for maketable below. Check $home against row

function myipfixup(&$row, &$rowdesc) {
  global $home;
  if($row['myIp'] == $home) {
    $row['myIp'] = "<span class='myhome'>" . $row['myIp'] . "</span>";
  }
  return false;
}                                   

[$myip] = $T->maketable($sql, array('callback'=>'myipfixup','attr'=>array('border'=>'1', 'id'=>'myip')));

$today = date("Y-m-d");

function mygeofixup(&$row, &$rowdesc) {
  global $today;
  
  $me = [
         'hFBzuVRDeIWdbhXmhZv7' => "HP",
         'e30hJHxUeaToTAB6g4Zv' => "TAB",
         'agvmgLtbOej09pGw27ZF' => "LAP",
         'Z1Kx9vql4QxiMB9brOd2' => "i12",
        ];
  
  foreach($me as $key=>$val) {
    if($row['finger'] == $key) {
      $row['finger'] .= "<span class='ME' style='color: red'> : $val</span>";
    }
  }
  
  if(strpos($row['lasttime'], $today) === false) {
    $row['lasttime'] = "<span class='OLD'>{$row['lasttime']}</span>";
  }
  return false;
}

$sql = "select lat, lon, finger, created, lasttime from geo order by lasttime desc";
[$mygeo] = $T->maketable($sql, array('callback'=>'mygeofixup', 'attr'=>array('border'=>'1', 'id'=>'mygeo')));

// Get the SiteId cookie

$cookies = $_COOKIE;

//vardump("cookie", $cookies);

// This is the standard bottom message

$msg =<<<EOF
<hr>
<h2><i>members</i> Table</h2>
$members
<h2><i>myip</i> Table</h2>
<p class='less-margin'>My HOME IP is in <span class='myhome'>GREEN</span></p>
$myip
<h2><i>geo</i> Table</h2>
<div id="outer">
<div id="geocontainer"></div>
<button id="removemsg">Click to remove map image</button>
</div>
<p>Click on table row to view map.<br>
<button id="showMe">Show Me</button>&nbsp;<button id="showAll">Show All</button>
</p>
$mygeo
EOF;

//$S->ip = "122.333.333.1"; // For testing only

[$cookieIp, $cookieEmail] = explode(":", $cookies['SiteId']);

if(!isMe($S) && $cookieEmail !== "bartonphillips@gmail.com" && !$DEBUG) {
  $msg = "<h1>No Cookie and Wrong IP</h1>"; // Just go away
  error_log("bartonphillips.com/test_examples/getcookie.php: ip=$S->ip, cookie=" . print_r($cookies, true) . ", agent=$S->agent :: Go Away");
} elseif(!$_COOKIE['SiteId']) {
  $msg = "<h1>No Cookie</h1>$msg"; // add $msg to no cookie

  // log some stuff

  if($DEBUG) {
    $msg = "$msg<br>ip: $S->ip<br>$members<br>$myip<br>";
  }
  //error_log("bartonphillips.com/test_examples/getcookie.php: ip=$S->ip, agent=$S->agent :: No Cookie");
} else {
  // This is the full message if we have a cookies

  $all = '';
  
  foreach($cookies as $key=>$cookie) {
    $all .= "<li><button class='reset'>Reset: <span>$key</span></button>$cookie</li>";
  }
  
  $msg = <<<EOF
<ul id='resetmsg'>$all</ul>
$msg
EOF;
}

// Render Page
// BLP 2021-10-26 -- Add 'form'

echo <<<EOF
$top
<div id="contain">
<form action="getcookie.php" method="post">
  Select Site:
  <select name='site'>
    <option>Bartonphillips</option>
    <option>Tysonweb</option>
    <option>Newbernzig</option>
  </select>

  <button type="submit" name='submit'>Submit</button>
</form>
<hr>
$msg
</div>
<hr>
$footer
EOF;
