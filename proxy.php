<?php
// BLP 2021-03-10 -- Proxy by passes all of the tracker.php and tracker.js logic. It writes a
// special string into the 'site' fields ($S->siteName . "Proxy") to identify this behavior.
// This is a proxy for the gitHub and others. It takes the query string and logs both counter2 and
// tracker info and then redirects to the query string.
  
$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setNoEmailErrs(true);
$_site->count = false; // Don't count
$_site->countMe = false; // Don't countMe

$S = new $_site->className($_site);

function checkUser($S) {
  $query = $_SERVER['QUERY_STRING'];
  $query = preg_replace("~blp=ingrid&~", '', $query, -1, $c);
  if(!$c) {
    echo "<h1>Go to our <a href='https://www.bartonphillips.com'>Home Page</a> or just Go Way.</h1>";
    $ref = ($_SERVER['HTTP_REFERER'] ?? "NO REFERED");
    error_log("PROXY-GO_AWAY: $ref, siteName: $S->siteName" . ", query: $query, agent: $S->agent, ip: $S->ip");
    exit();
  } else {
    error_log("PROXY-OK_$S->ip - query: $query, agent: $S->agent, ip: $S->ip");
  }
  return $query;
};

$query = checkUser($S);

$trackersite = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Put info into counter2
if($S->isBot) {
  $bot = 1;
  $count = 0;
} else {
  $bot = 0;
  $count = 1;
}

// BLP 2021-03-10 -- no more member info. I have removed trackmember() from SiteClass.

$query = substr($query, 0, 254);
$query = $S->escape($query);

// siteName plus "Proxy"
$site = $S->siteName . "Proxy";

// So the site in counter2 will have Proxy added to the site name.

$S->query("insert into $S->masterdb.counter2 (site, date, filename, count, bots, lasttime) ".
          "values('$site', now(), '$query', $count, $bot, now()) ".
          "on duplicate key update count=count+$count, bots=bots+$bot, lasttime=now()");

$agent = $S->escape($S->agent);
$ip = $S->ip;
$refid = $S->refid;

// Put info into tracker.
// BLP 2021-03-10 -- removed $trackBot and added zero. Not sure where $trackBot came from?
$trackersite = substr($trackersite, 0, 250); // make sure it is not too long.

$S->query("insert into $S->masterdb.tracker (site, page, ip, agent, refid, isJavaScript, starttime, lasttime) ".
          "values('$site', '$trackersite', '$ip', '$agent', '$refid', 0, now(), now())");

//$S->query("update $S->masterdb.tracker set page='$trackersite', lasttime=now() where id=$S->LAST_ID");

//error_log("Query: $query");

header("Location: $query");
