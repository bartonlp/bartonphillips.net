<?php
$_site = require_once(getenv("SITELOADNAME"));
$_site->noTrack = true;
$_site->noGeo = true;
$_site->nofooter = true;
$S = new $_site->className($_site);

$ip = $S->ip;

$h->title = "CookieLess";
//$h->banner = "<h1>This Server is Only for Serving Content to My Sites<br>Please Go Away</h1>";
$h->keywords = "CookieLess Domain";
$h->desc = "This is a cookieless domain used to feed css, images and js to my other domains";

$h->css =<<<EOF
  <style>
input[type='submit'] {
  font-size: 1rem;
  padding: .5rem;
  background-color: pink;
  border-radius: .5rem;
}
#form {
  text-align: center;
}
  </style>
EOF;

list($top, $footer) = $S->getPageTopBottom($h);

echo <<<EOF
$top
<header>
<img src='https://bartonphillips.net/images/blp-image.png'>
<h1>This Server is Only for Serving Content to My Sites</h1>
</header>
<div id="form">
<form action="https://www.bartonphillips.com/showmarkdown.php" method="post">
<input type='hidden' name='filename' value="https://bartonphillips.net/README.md">
<input type='hidden' name='type' value="GitHub">
<input type='submit' value="View the README.md file">
</form>
</div>
$footer
EOF;
