<?php

require('sfCore.php');



$pass = '12345';




echo 'Comparison (sha1): <br />';
$time_start = microtime(true);
for($i=0;$i<1000;$i++){
    $hash = sha1($pass);
}

$duration = microtime(true) - $time_start;
echo 'SHA 1 Hash: ' . $hash . " (took $duration seconds)<br /><br />";




echo 'Hashing password ' . $pass . ':<br />';
flush();
$time_start = microtime(true);
$hash = sfBcrypt::hash($pass, 13);
$duration = microtime(true) - $time_start;
echo 'Hash: ' . $hash . " (took $duration seconds)<br /><br />";
echo "Checking hash... ";
flush();



$time_start = microtime(true);
if(sfBcrypt::check($pass, $hash))
{
    $duration = microtime(true) - $time_start;
    echo "OK (took $duration seconds)";
}else{
    $duration = microtime(true) - $time_start;
    echo "FAIL (took $duration seconds)";
}

?>