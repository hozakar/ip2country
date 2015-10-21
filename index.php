<?php
error_reporting(0);
header('Content-type: text/plain; charset=utf-8');

include __DIR__ . '/IPList.class.php';

$iplist = new IPList( array( "host" => "localhost", "dbname" => "iplist_db", "user" => "username", "pass" => "password" ) );

if($_GET['ip']) {
    echo $iplist->getByIp($_GET['ip']);
} else {
    echo $iplist->getByCountry($_GET['country']);
}