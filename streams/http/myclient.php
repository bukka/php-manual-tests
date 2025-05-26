<?php

define('PHP_CLI_SERVER_ADDRESS', "localhost:8000");

//var_dump(file_get_contents('http://localhost:8000/index.php?status=302&loc=1'));



//echo file_get_contents("http://".PHP_CLI_SERVER_ADDRESS."/index.php");
echo "default\n";
$codes = array(301, 302);
foreach($codes as $code) {
    echo "$code: ".file_get_contents("http://".PHP_CLI_SERVER_ADDRESS."/index.php?status=$code&loc=1");
}
// echo "follow=0\n";
// $arr = array('http'=>
//                         array(
//                                 'follow_location'=>0,
//                         )
//                 );
// $context = stream_context_create($arr);
// foreach($codes as $code) {
//     echo "$code: ".file_get_contents("http://".PHP_CLI_SERVER_ADDRESS."/index.php?status=$code&loc=1", false, $context);
// }
// echo "follow=1\n";
// $arr = array('http'=>
//                         array(
//                                 'follow_location'=>1,
//                         )
//                 );
// $context = stream_context_create($arr);
// foreach($codes as $code) {
//     echo "$code: ".file_get_contents("http://".PHP_CLI_SERVER_ADDRESS."/index.php?status=$code&loc=1", false, $context);
// }