<?php

require('../vendor/autoload.php');

echo "running";
error_log("hello, this is a test!");
$x = print_r($_GET,true);
error_log($x);

?>
