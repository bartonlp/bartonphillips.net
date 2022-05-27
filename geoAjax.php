<?php
// This is the Ajax for webstats.js and geo.js
// This needs to be symlinked into each directory where it will be run along with getcookie.php and
// webstats.php. The symlink is needed so $S will have the information form the local
// mysitemap.json. This is needed for setSiteCookie().
// *** Remember, this does not happen untill the entire page has been rendored so the
// fingerprint and tracker info are not available for the PHP files!

$_site = require_once(getenv("SITELOADNAME"));
$_site->noTrack = true;
$S = new $_site->className($_site);

//$DEBUG = true;

// Ajax for getcookie.php

if($_POST['page'] == 'reset') {
  $cookie = $_POST['name'];

  if($S->setSiteCookie($cookie, '', -1) === false) {
    error_log("geoAjax.php: remove cookie Error");
    echo "geoAjax.php: remove cookie Error";
  } else {
    //error_log("geoAjax.php: remove cookie OK");
    echo "geoAjax.php: remove cookie OK";
  }
  exit();
}

// BLP 2021-10-07 -- AJAX for geo.js used in index.php for bartonphillips.com, tysonweb and
// newbernzig.com (also bartonphillips.org on HP).
/*
CREATE TABLE `geo` (
  `lat` varchar(50) NOT NULL,
  `lon` varchar(50) NOT NULL,
  `finger` varchar(100) DEFAULT NULL,
  `site` varchar(100) DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

if($_POST['page'] == 'geo') {
  $lat = $_POST['lat'];
  $lon = $_POST['lon'];
  $visitor = $_POST['visitor'];
  $id = $_POST['id'];
  $site = $_POST['site']; // This is the site that actually made the request
  $ip = $_POST['ip'];

  //error_log("geo: ip=$ip");
  
  $site = $site ?? $S->siteName;
  
  // We have the lat and long and visitor so set nogeo to false (the default is NULL).
  
  $S->query("update $S->masterdb.tracker set nogeo=0 where id='$id'");
  
  $exp = time() + 60*60*24*365;

  if($S->setSiteCookie("BLP-geo", "$lat:$lon", $exp) === false) {
    error_log("geoAjax: setSiteCookie geo Error: lat:lon=$lat:$lon, exp=$exp");
    echo "geoAjax: setSiteCookie geo Error";
    exit();
  }

  $sql = "select lat, lon from $S->masterdb.geo where site='$site' and finger='$visitor' and ip='$ip'";
  $x = $S->query($sql);

  // If lat and lon is the same as what we just found update
  
  while([$slat, $slon] = $S->fetchrow('num')) {
    $found = 0;
    if($slat === $lat && $slon === $lon) {
      ++$found;
      // We use $site instead of $S->siteName as they may be different.
      
      $sql = "update $S->masterdb.geo set lasttime=now(), ip='$ip' where lat=$slat and lon=$slon and site='$site' and finger='$visitor'";
      $S->query($sql);

      //if($DEBUG) error_log("geoAjax $id, $site, $ip -- geo Updated");
      //echo "geo Update: $id, $site, $ip";
      //exit();
    }
    if($found) {
      if($DEBUG) error_log("geoAjax $id, $site, $ip -- geo Updated: found=$found");
      echo "geo Update: $id, $site, $ip";
      exit();
    }
  }

  // This is either a new visitor or the lat and lon are not the same as before. Insert.
  
  $sql = "insert into $S->masterdb.geo (lat, lon, finger, site, ip, created, lasttime) values('$lat', '$lon', '$visitor', '$site', '$ip', now(), now())";
  $S->query($sql);

  if($DEBUG) error_log("geoAjax $id, $site, $ip -- geo Insert");
  echo "geo Insert: $id, $site, $ip";
  exit();
}

if($_POST['page'] == 'geoFail') {
  $id = $_POST['id'];
  
  $S->query("update $S->masterdb.tracker set nogeo=1 where id='$id'");

  if($DEBUG) error_log("geoAjax $id, $site, $ip -- geoFail");
  echo "geoFail: id=$id";
  exit();
}

// Ajax for finger. Remember, this does not happen untill the entire page has been rendored so the
// fingerprint and tracker are not available for the PHP files!

if($_POST['page'] == 'finger') {
  $visitor = $_POST['visitor'];
  $id = $_POST['id'];

  $exp = time() + 60*60*24*365;
  
  if($S->setSiteCookie("BLP-Finger", $visitor, $exp) === false) {
    error_log("geoAjax: setSiteCookie Finger Error");
    echo "geoAjax: setSiteCookie Finger Error"; // This is returned to the javascript that called this.
    exit();
  }

  // tracker table was created in SiteClass

  $sql = "update $S->masterdb.tracker set finger='$visitor' where id=$id";
  $S->query($sql);

  if($DEBUG) error_log("geoAjax $id, $visitor -- finger Updated");
  echo "finger Updated: $id, $visitor"; // Returned to the javascript.
  exit();
}
