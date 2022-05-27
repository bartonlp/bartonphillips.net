<?php
// The .htaccess file has: ReWriteRule ^robots.txt$ robots.php [L,NC]
// This file reads the rotbots.txt file and outputs it and then gets the user agent string and
// saves it in the bots table.
/*
CREATE TABLE `bots` (
  `ip` varchar(40) NOT NULL DEFAULT '',
  `agent` text NOT NULL,
  `count` int DEFAULT NULL,
  `robots` int DEFAULT '0',
  `site` varchar(255) DEFAULT NULL, // this is $who which can be multiple sites seperated by commas.
  `creation_time` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`,`agent`(254))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bots2` (
  `ip` varchar(40) NOT NULL DEFAULT '',
  `agent` text NOT NULL,
  `page` text,
  `date` date NOT NULL,
  `site` varchar(50) NOT NULL DEFAULT '', 
  `which` int NOT NULL DEFAULT '0',
  `count` int DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
PRIMARY KEY (`ip`,`agent`(254),`date`,`site`,`which`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
*/

$_site = require_once(getenv("SITELOADNAME"));
require_once(SITECLASS_DIR . "/defines.php");

$S = new Database($_site);

$robots = file_get_contents($S->path."/robots.txt");
echo $robots;

// Get info from myip. NOTE Database does not have isMe()!

$S->query("select myIp from $S->masterdb.myip");
while($myIp = $S->fetchrow('num')[0]) {
  if($myIp == $S->ip) return;
}

if($S->ip == '157.245.129.4') return;

$agent = $S->agent;
$ip = $S->ip;

try {
  // BLP 2021-12-26 -- robots is 1 if we do an insert or robots=robots|2 

  $S->query("insert into $S->masterdb.bots (ip, agent, count, robots, site, creation_time, lasttime) ".
            "values('$ip', '$agent', 1, " . BOTS_ROBOTS . ", '$S->siteName', now(), now())");
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

    $S->query("update $S->masterdb.bots set robots=robots|" . BOTS_ROBOTS . ", count=count +1, site='$who', lasttime=now() ".
              "where ip='$ip'");
  } else {
    error_log("robots: ".print_r($e, true));
  }
}

// BLP 2021-11-12 -- 2 is for seen by robots.php.
// BLP 2021-12-26 -- bots2 primary key is 'ip, agent, date, site, which'

$S->query("insert into $S->masterdb.bots2 (ip, agent, date, site, which, count, lasttime) ".
          "values('$ip', '$agent', now(), '$S->siteName', " . BOTS_ROBOTS . ", 1, now()) ".
          "on duplicate key update count=count+1, lasttime=now()");
