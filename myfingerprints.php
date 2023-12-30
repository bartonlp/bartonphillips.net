<?php
// BLP 2021-11-11 -- These are my fingerprints. This file is used by getcookie.php and webstats.php
// (maybe more).
// I 'require_once()'.

// BLP 2023-10-18 - I think I will always have $S by the time I call this.

if(!class_exists("Database")) header("location: https://bartonlp.com/otherpages/NotAuthorized.php");

return [
        '4cd66330bccc25864cbf353a7ba37c6a' => "HP",      // BLP 2023-09-23 - new kernel
        '59e64000697ffc9ef2c4652e7361236d' => "i14",     // my iPhone
        'e4e1a1ded6fa497d5375479a69c64758' => "SAMa10e", // my Phone (android)
        '083f355e8f14ce4479ab8982bb758dc1' => "BonnieWindows",
        '3a189795dcd46d0c7e85e9b9cc9356c3' => "TAB",     // My tablet
        //'' => "BiPhone", // bonnie's iPhone
        //'' => "AcerSpinUbuntu",    // Acer Spin 3 Ubuntu
       ];

