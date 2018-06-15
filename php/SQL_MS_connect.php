<?php
require_once("unitalsi_include_common.php");
$dsn = 'dblib:host=89.96.54.223:1433;dbname=unitmz';
$user = 'biassoni';
$password = 'bernadette';
$connectionInfo = array( "Database"=>"unitmz", "UID"=>"biassoni", "PWD"=>"bernadette");
// Microsoft SQL Server using the SQL Native Client 10.0 ODBC Driver - allows connection to SQL 7, 2000, 2005 and 2008
//$connection = odbc_connect("Driver={SQL Server Native Client 10.0};Server='89.96.54.223';Database='unitamz';", $user, $password);
//$conn = sqlsrv_connect( $serverName, $connectionInfo);
$conn = mssql_connect("89.96.54.223", "$user", "$password");
   //$conn = new mysqli('89.96.54.223:1433', $user, $password, 'unimz');
if( $conn ) {
     echo "Connection established.\n<br>";
}else{
	  echo "Error = " . mssql_get_last_message() ;
     echo "Connection could NOT be established.\n<br />";
     //die( print_r( sqlsrv_errors(), true));
}


try {
     $conn = new \PDO($dsn, $user,$password);
     if( $conn ) {
        echo "Connection established.\n<br>";
      } else {
	        echo "Error = " . mssql_get_last_message() ;
           echo "Connection could NOT be established.\n<br />";
     //die( print_r( sqlsrv_errors(), true));
   }
   } catch (PDOException $e) {
    echo $e->getMessage();
}
?>