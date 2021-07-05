<?php
// Register yours name and email address
// This is for index.php

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);
$S = new $_site->className($_site);

$ip = $S->ip;
$agent = $S->agent;
$site = $S->siteName;

$h->title = "Register";
$h->css = <<<EOF
  <style>
.lynx {
  display: none;
}
  </style>
  <!--[if lynx]>
  <style>
.lynx {
  display: block;
}
  </style>
  <![endif]-->
  <style>
input {
  font-size: 1rem;
  padding-left: .5rem;
}
input[type="submit"] {
  border-radius: .5rem;
  background-color: green;
}
  </style>
EOF;

list($top, $footer) = $S->getPageTopBottom($h);

if($_POST) {
  $name = $S->escape($_POST['name']);
  $email = $S->escape($_POST['email']);

  /* BLP 2021-03-09 -- members is a table in the dbinfo structure in mysitemap.json.
     The masterdb has the main database which is usually 'barton'.
     For bartonphillips.com the dbinfo->database has the 'bartonphillips' database.
  */

  try {
    $sql = "insert into members (name, email, ip, agent, created, lasttime) ".
           "values('$name', '$email', '$ip', '$agent', now(), now())";

    if(!$S->query($sql)) {
      echo "Error in Register POST<br>";
      throw(new Exception("register.php", $this));
    }
    $id = $S->getLastInsertId();
  } catch(Exception $e) {
    // If we failed to do an insert
    // then try an update with name and email which are unique keys.
    
    $sql = "update members set ip='$ip', agent='$agent', lasttime=now() ".
           "where name='$name' and email='$email'";
    
    $S->query($sql);

    // Now we need to get the id from the update.
    
    $sql = "select id from members where name='$name'";
    $S->query($sql);
    list($id) = $S->fetchrow('num');
  }
  
  if($S->setSiteCookie('SiteId', "$id", date('U') + 31536000, '/') === false) {
    echo "Can't set cookie in register.php<br>";
    throw(new Exception("Can't set cookie register.php " . __LINE__));
  }
  header("Location: /");
  exit();
}

// Start Page

echo <<<EOF
$top
<h1>Register</h1>
<form method="post">
<p>Get our newsletter once a month. The newsletter has information on new and updated projects by me.
We currently have several projects:</p>

<ul>
<li>SiteClass: A mini framework for small sites.
  <a href="https://github.com/bartonlp/site-class">SiteClass on GitHub</a></li>
<li>SlideShow: A slideshow of images from a local or remote site.
  <a href="https://github.com/bartonlp/slideshow">Slideshow on GitHub</a></li>
<li>MySqlSlideshow: A slideshow of images via a MySql table.
  <a href="https://github.com/bartonlp/mysqlslideshow">MySqlSlideshow on GitHub</a></li>
<li>Blog Updates</li>
</ul>

<table>
<tbody>
<tr>
<td><span class="lynx">Enter Name </span><input type="text" name="name" autofocus required placeholder="Enter Name"></td>
</tr>
<tr>
<td><span class="lynx">Enter Email Address </span><input type="text" name="email" autofocus required placeholder="Enter Email Address"></td>
</tr>
</tbody>
</table>
<input type="submit" value="Submit">
</form>
$footer
EOF;
