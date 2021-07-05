<?php
// BLP 2016-02-18 -- This file is a substitute for Sitemap.xml. This file is RewriteRuled in
// .htaccess to read Sitemap.xml and output it. It also writes a record into the bots table

$_site = require_once(getenv("SITELOADNAME"));
$S = new Database($_site);

if(!file_exists($S->path . "/Sitemap.xml")) {
  echo "NO SITEMAP<br>";
  exit();
}

$sitemap = file_get_contents($S->path."/Sitemap.xml");
echo $sitemap;

$ip = $_SERVER['REMOTE_ADDR'];
if($S->myUri) {
  if(is_array($S->myUri)) {
    foreach($S->myUri as $v) {
      if($ip == gethostbyname($v)) {
        return;
      }
    }
  } else {
    if($ip == gethostbyname($S->myUri)) {
      return;
    }
  }
}

$agent = $S->escape($_SERVER['HTTP_USER_AGENT']);

//error_log("sitemap: $S->siteName, $ip, $agent");

$S->query("select count(*) from information_schema.tables ".
           "where (table_schema = '$S->masterdb') and (table_name = 'bots')");

list($ok) = $S->fetchrow('num');
      
if($ok == 1) {
  try {
    $S->query("insert into $S->masterdb.bots (ip, agent, count, robots, site, creation_time, lasttime) ".
               "values('$ip', '$agent', 1, 16, '$S->siteName', now(), now())");
  }  catch(Exception $e) {
    if($e->getCode() == 1062) { // duplicate key
      $S->query("select site from $S->masterdb.bots where ip='$ip'");
      list($who) = $S->fetchrow('num');
      if(!$who) {
        $who = $S->siteName;
      }
      if(strpos($who, $S->siteName) === false) {
        $who .= ", $S->siteName";
      }
      $S->query("update $S->masterdb.bots set robots=robots | 32, site='$who', count=count+1, lasttime=now() where ip='$ip'");
    } else {
      error_log("sitemap: ".print_r($e, true));
    }
  }
} else {
  error_log("sitemap: $S->siteName bots does not exist in $S->masterdb database");
}

$S->query("select count(*) from information_schema.tables ".
           "where (table_schema = '$S->masterdb') and (table_name = 'bots2')");

list($ok) = $S->fetchrow('num');
      
if($ok == 1) {
  $S->query("insert into $S->masterdb.bots2 (ip, agent, date, site, which, count, lasttime) ".
             "values('$ip', '$agent', current_date(), '$S->siteName', 4, 1, now()) ".
             "on duplicate key update count=count+1, lasttime=now()");
} else {
  error_log("sitemap: $S->siteName bots does not exist in $S->masterdb database");
}
