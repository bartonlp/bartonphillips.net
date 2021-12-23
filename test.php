<?php
$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);  
$S = new $_site->className($_site);

vardump($_GET);

echo (isset($_GET['csstest']) ? "true" : "false") . "<br>";
echo (is_null($_GET['csstest']) ? "true" : "false") . "<br>";