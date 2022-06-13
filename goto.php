<?php
// BLP 2021-03-10 -- Proxy by passes all of the tracker.php and tracker.js logic. It writes a
// special string into the 'site' field ($S->siteName . "Proxy") to identify this behavior.
// This is a proxy for the gitHub and others. It takes the query string and logs both counter2 and
// tracker info and then redirects to the query string.
  
$_site = require_once(getenv("SITELOADNAME"));
require_once(SITECLASS_DIR . '/defines.php'); // Get TRACKER constants.

// These override anything in mysitemap.json.

$_site->count = false; // Don't count
$_site->noTrack = true; // don't track anything as we do it here.
$_site->nofooter = true;; // we want the no footer.

$S = new $_site->className($_site);

// AJAX from inlineScript.

if($_POST['page'] == 'finger') {
  extract($_POST); // tracker, visitor=visitorId, agent, err, ip, query

  // Put info into tracker.

  $java = TRACKER_GOTO;
  
  if($visitor) { // This could be a finger or '' if a bot.
    if($S->isBot) {
      $java |= TRACKER_BOT;
    }
    $visitor = $visitor ?? "BOT";
  } 

  $S->query("insert into $S->masterdb.tracker (site, page, finger, ip, agent, isJavaScript, starttime, lasttime) ".
          "values('$S->siteName', '$tracker', '$visitor', '$ip', '$agent', $java, now(), now())");

  error_log("GOTO-$err: siteName: $S->siteName, trackerSite: $tracker, finger: $visitor, isJavaScript: " . dechex($java) . ", ip: $ip, query: $query, agent: $agent");
  echo "OK";
  exit();
}

// Check if secret code is set.

function checkUser($S) {
  $query = $_SERVER['QUERY_STRING'];
  $query = preg_replace("~blp=ingrid&~", '', $query, -1, $c); // limit = -1 (no limit), $c is the count of replacements
  //$c = null; // For testing
  // $c is 1 if 'blp=ingrid&' was replaced with ''
  
  if(!$c) { // $c is not 1
    $msg = "<h1>Go to our <a href='https://www.bartonphillips.com'>Home Page</a> or just Go Way.</h1>";
    $err = "GoAway";
    error_log("goto.php $S->siteName: ip=$S->ip secret blp string incorrect, $query");
  } else { // $c is 1
    $msg = null;
    $err = "OK";
  }
  return [$query, $msg, $err];
};

[$query, $msg, $err] = checkUser($S);

$query = substr($query, 0, 254); // Make sure it is only 254 character long
$query = $S->escape($query); // Make sure there are no ' etc.

// Because 'count', 'countMe' and 'noTrack' SiteClass does not count. We do the counting for
// counter, counter2 and tracker here.
// The 'site' in counter and counter2 will have Proxy added to the $S->siteName.

$site = $S->siteName . "Proxy"; // Add Proxy to the site name.

[$bot, $real, $count] = $S->isBot ? [1,0,0] : [0,1,1];

$trackersite = substr($_SERVER['REQUEST_URI'], 0, 250); // This is the site that called this.

$S->query("insert into $S->masterdb.counter2 (site, date, filename, `real`, bots, lasttime) ".
          "values('$site', now(), '$trackersite', $real, $bot, now()) ".
          "on duplicate key update `real`=`real`+$real, bots=bots+$bot, lasttime=now()");

$S->query("insert into $S->masterdb.counter (filename, site, count, realcnt, lasttime) ".
          "values('$trackersite', '$site', 1, $bot, now()) ".
          "on duplicate key update count=count+1, realcnt=realcnt+$real, lasttime=now()");

$agent = $S->agent;
$ip = $S->ip;

$h->title = "GOTO";
$h->banner = $err == "OK" ? "<h1>Redirect Page</h1>" : "<h1>You Did Not Come From My Home Page</h1>";
$h->css = "h1 { text-align: center }";

// Set up javascript. Add the variables. Do the promise then do it and ajax to this file.

$h->inlineScript = <<<EOF
const tracker = "$trackersite", agent = "$agent", ip = "$ip", err = "$err", query = "$query";
const FINGER_TOKEN = "QpC5rn4jiJmnt8zAxFWo";

const fpPromise = new Promise((resolve, reject) => {
  const script = document.createElement('script');
  script.onload = resolve;
  script.onerror = reject;
  script.async = true;
  script.src = 'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs-pro@3/dist/fp.min.js';                 
  //               + '@fingerprintjs/fingerprintjs@3/dist/fp.min.js';
  document.head.appendChild(script)
})
.then(() => FingerprintJS.load({ token: FINGER_TOKEN, endpoint: 'https://fp.bartonphillips.com'}));

// Get the visitor identifier (fingerprint) when you need it.

fpPromise
.then(fp => fp.get())
.then(result => {
  // This is the visitor identifier:
  const visitorId = (result.visitorId == '') ? null : result.visitorId;

  console.log("visitor: ", visitorId);
  $.ajax({
    url: "goto.php",
    data: {
      page: 'finger',
      tracker: tracker,
      visitor: visitorId,
      agent: agent,
      err: err,
      ip: ip,
      query: query
    },
    type: 'post',
    success: function(data) {
      console.log("return: " + data);
      // If we did NOT have an error redirect the query.
      
      if(err == "OK") {
        setTimeout(function(){
          window.location.href = query;
        }, 10) // redirect in 10 seconds
      }
    },
    error: function(err) {
      console.log(err);
      $("header h1").html("<h1>Ajax Error</h1>");
    }
  });
});
EOF;
   
[$top, $footer] = $S->getPageTopBottom($h, $b);

echo <<<EOF
$top
$msg
$footer
EOF;
