<?php

require_once('sfCore.php');


// basic hooking
$db = new fDatabase('mysql', 'experiments', 'root', 'root', 'localhost');
sfUsers::hookDatabase($db);
fORMDatabase::attach($db);
sfCore::attach($db);

fTimestamp::setDefaultTimezone('Asia/Jakarta');




/*
echo 'Executing Request.' . "\n";

if(fRequest::get('class')){
	call_user_func_array(fRequest::get('class') . '::' . fRequest::get('method'), fRequest::get('param', 
		'array'));
}
echo "\n" . 'Processing Complete.' . "\n";

*/
?>