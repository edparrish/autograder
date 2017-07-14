<?php
/**
Configuration settings for Autograder.
*/
define( 'ROOT_DIR', dirname(__FILE__) );

/*
The canvasDomain is the URL for the Canvas API. For example, the URL for
Cabrillo College would be
"https://cabrillo.instructure.com/api/v1".

The token is a cryptographically unique string of characters and numbers you
must generate in Canvas. To generate one, login to Canvas, go to
"Accounts" and "Settings" and click on the "New Access Token" button.
*/
// Set the following to your college domain.
$canvasDomain = 'college.instructure.com';
// Generate the token in Canvas and assign it here.
$token = 'put token between quotes';
?>
