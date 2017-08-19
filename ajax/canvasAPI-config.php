<?php
/**
This page contains configuration variables for the CanvasAPI.php page.

The canvasDomain is the URL for the Canvas API. For example, the URL for
Cabrillo College would be
"cabrillo.instructure.com".

The token is a cryptographically unique string of characters and numbers you
must generate in Canvas. To generate one, login to Canvas, go to
"Accounts" and "Settings" and click on the "New Access Token" button.
see: https://community.canvaslms.com/docs/DOC-10806

Windows needs an SSL CA cert from: https://curl.haxx.se/docs/caextract.html
To update, download cacert.pem from the page and replace the file in folder ajax.

To display the correct date and time, update the TIMEZONE setting. For timezones see:
http://php.net/manual/en/timezones.php

@author Ed Parrish Moved config variables from canvasAPI.php.
*/
// Set the following to your college domain.
$canvasDomain = 'your_college.instructure.com';
// Generate the token in Canvas and assign it here.
$token = 'put token here';
// Path to the SSL CA certificate
define("CACERT_PATH", realpath("./cacert.pem"));
// Timezone for manager.php, see: http://php.net/manual/en/timezones.php
define("TIMEZONE", "America/Los_Angeles");
