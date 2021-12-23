<?php
// BLP 2021-08-22 -- Changed the name of the file to goto.php to try to reduce the number of
// instances of proxy being used to goto porn sites.
// BLP 2021-03-10 -- Proxy by passes all of the tracker.php and tracker.js logic. It writes a
// special string into the 'site' fields ($S->siteName . "Proxy") to identify this behavior.
// This is a proxy for the gitHub and others. It takes the query string and logs both counter2 and
// tracker info and then redirects to the query string.
  
$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setNoEmailErrs(true);
$_site->count = false; // Don't count
$_site->countMe = false; // Don't countMe
$_site->noTrack = true; // don't track anything as we do it here.

$S = new $_site->className($_site);

if($_POST['page']) {
  extract($_POST);
  //error_log("TEST: ". print_r($_POST, true));
  
  error_log("GOTO-$err: siteName: $S->siteName, finger: $visitor, ip: $ip, query: $query, agent: $agent,  ref: $ref");
  echo "OK";
  exit();
}

function checkUser($S) {
  $query = $_SERVER['QUERY_STRING'];
  $query = preg_replace("~blp=ingrid&~", '', $query, -1, $c); // $c is the count of replacements
  //$c = null;
  if(!$c) {
    $msg = "<h1>Go to our <a href='https://www.bartonphillips.com'>Home Page</a> or just Go Way.</h1>";
    $err = "GoAway";
  } else {
    $err = "OK";
  }
  return [$query, $msg, $err];
};

[$query, $msg, $err] = checkUser($S);

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

// Because 'count', 'countMe' and 'noTrack' SiteClass does not count. We do the counting for
// counter, counter2 and tracker here.
// The 'site' in counter and counter2 will have Proxy added to the $S->siteName.

$site = $S->siteName . "Proxy";
$bot = $S->isBot ? 1 : 0;
$real = $bot == 1 ? 0 : 1;
$trackersite = substr($_SERVER['REQUEST_URI'], 0, 250);

$S->query("insert into $S->masterdb.counter2 (site, date, filename, count, bots, lasttime) ".
          "values('$site', now(), '$trackersite', $real, $bot, now()) ".
          "on duplicate key update count=count+$real, bots=bots+$bot, lasttime=now()");

$S->query("insert into $S->masterdb.counter (filename, site, count, realcnt, lasttime) ".
          "values('$trackersite', '$site', 1, $bot, now()) ".
          "on duplicate key update count=count+1, realcnt=realcnt+$real, lasttime=now()");

$agent = $S->escape($S->agent);
$ip = $S->ip;
$refid = $S->escape($S->refid) ?? "NONE";

// Put info into tracker.

$S->query("insert into $S->masterdb.tracker (site, page, ip, agent, refid, isJavaScript, starttime, lasttime) ".
          "values('$site', '$trackersite', '$ip', '$agent', '$refid', 0, now(), now())");

$h->title = "GOTO";
$h->banner = $err == "OK" ? "<h1>Redirect Page</h1>" : "<h1>You Did Not Come From My Home Page</h1>";
$h->css = "<style>h1 { text-align: center }</style>";

// Set up javascript. Add the variables. Do the promise then do it and ajax to this file.

$b->script = <<<EOF
<script>
const agent = "$agent", ref = "$refid", ip = "$ip", err = "$err", query = "$query";
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
  const visitorId = result.visitorId;
    
  console.log("visitor: " + visitorId);
  $.ajax({
    url: "goto.php",
    data: {
      page: 'finger',
      visitor: visitorId,
      agent: agent,
      err: err,
      ip: ip,
      ref: ref,
      query: query
    },
    type: 'post',
    success: function(data) {
      console.log("return: " + data);

      // If we did NOT have an error redirect the the query.
      
      if(err == "OK") {
        setTimeout(function(){
          window.location.href = query;
        }, 10)
      }
    },
    error: function(err) {
      console.log(err);
      $("header h1").html("<h1>Ajax Error</h1>");
    }
  });
});
</script>
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

echo <<<EOF
$top
$msg
$footer
EOF;

