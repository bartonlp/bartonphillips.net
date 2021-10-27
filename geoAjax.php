<?php
// BLP 2021-10-25 -- This is the Ajax for webstats.js and geo.js
// This needs to be symlinked into each directory where it will be run along with getcookie.php and
// webstats.php. The symlink is needed so $S will have the information form the local
// mysitemap.json. This is needed for setSiteCookie().

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);
$S = new $_site->className($_site);

//error_log("S" . print_r($S, true));
//error_log("Host: {$_SERVER['SERVER_NAME']}");

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
// newbernzig.com

if($_POST['page'] == 'geo') {
  $lat = $_POST['lat'];
  $lon = $_POST['lon'];
  $visitor = $_POST['visitor'];

  $exp = time() + 60*60*24*365;
  
  if($S->setSiteCookie("BLP-geo", "$lat:$lon", $exp) === false) {
    error_log("geoAjax: setSiteCookie geo Error");
    echo "geoAjax: setSiteCookie geo Error";
    exit();
  }

  if($S->setSiteCookie("BLP-Finger", $visitor, $exp) === false) {
    error_log("geoAjax: setSiteCookie Finger Error");
    echo "geoAjax: setSiteCookie Finger Error";
    exit();
  }
  
  //error_log("From geoAjax: $lat, $lon, $visitor");

  $sql = "select lat, lon, finger from geo where finger = '$visitor'";
  $S->query($sql);

  // If lat and lon is the same as what we just found update
  
  while([$slat, $slon] = $S->fetchrow('num')) {
    if($slat === $lat && $slon === $lon) {
      //error_log("lat and lon for $visitor exists so update lasttime");
      $sql = "update geo set lasttime=now() where lat=$slat and lon=$slon and finger='$visitor'";
      $S->query($sql);
      echo "Update";
      exit();
    }
  }

  //error_log("New $lat, $lon, $visitor");
  
  // This is either a new visitor or the lat and lon are not the same as before. Insert.
  
  $sql = "insert into geo (lat, lon, finger, created, lasttime) values('$lat', '$lon', '$visitor', now(), now())";
  $S->query($sql);

  echo "Insert";
  exit();
}
