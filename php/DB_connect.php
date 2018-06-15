<?php
/******************************************
*
*	Stabilisce la connessione col DB applicativo Mysql
*
*	History: prima versione Marzo 2016
*
*******************************************/
function DB_connect() {
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
require_once('../php/ritorna_path_to_key.php');

	$debug=false;
	
   $host=null;
   $user=null;
   $pwd=null;
   $db_name=null;
   $versione=null;
   $db_space=null;
   $gc=null; // Google Calendar
   
   $info=array();
   $readFromFile=true;
   $index=0;
   $keyFile=null;

	 if(session_status() == PHP_SESSION_NONE)      
       session_start();
       
   if(isset($_SESSION["sql_db"])) { // Dati DB in sessione, non leggo il file
       if($debug)
          echo "Dati accesso al DB in Session";
       $db_name = $_SESSION["sql_db"];
       $user = $_SESSION["sql_user"];
       $host = $_SESSION["sql_host"];
       $pwd = $_SESSION["sql_pwd"];
       $readFromFile=false;       
      }
      
    if($readFromFile) { // Dati DB NON in sessione, leggo il file
        $activationFile=ritorna_path_to_key();

	    $keyFile  = file_get_contents($activationFile);
	    $rows = explode("\n", $keyFile);
	    $totRows=count($rows);
	    $totRows--;

	    if($debug)
	       echo "Totale righe file = " . $totRows;
  
	    foreach($rows as $row => $data) {
		          if($index >= $totRows)
		              break;

                //get row data
                 if( preg_match("/^#/", preg_quote($rows[$index])))  {// Comment
                      if($debug)
                          echo "Comment (ignoring) = " . $rows[$index];
                      $index++;
                      continue;
                    }

                 $rows[$index] = rtrim($rows[$index]);
                 $row_data = explode("=", $data);
                
                 $info[$row]['id']           = $row_data[0];
                 $info[$row]['value']      = $row_data[1];
                 
                 if($debug) {
                     echo $activationFile . " ID = " .$info[$row]['id']  .  " VALUE = " . $info[$row]['value'] . "\n";
                    }
                 if($info[$row]['id'] == 'hostDB') // found host
                     $host = $info[$row]['value'];
                     

                 if($info[$row]['id'] == 'userDB') // found user
                     $user = $info[$row]['value'];

                 if($info[$row]['id'] == 'pwdDB') // found pwd
                     $pwd = $info[$row]['value'];

                 if($info[$row]['id'] == 'nameDB') // found nome DB
                     $db_name = $info[$row]['value'];

                 if($info[$row]['id'] == 'version') // found version
                     $versione = $info[$row]['value'];
 
                 if($info[$row]['id'] == 'spaceDB') // found space available
                     $db_space = $info[$row]['value'];
 
                 if($info[$row]['id'] == 'GoogleCalendar') // found Google Calendar available
                     $gc = $info[$row]['value'];

                $index++;
                }
       $host=rtrim($host);
       $user=rtrim($user);
       $pwd=rtrim($pwd);
       $db_name=rtrim($db_name);
       $versione=rtrim($versione);
       $db_space=rtrim($db_space);

       $_SESSION["sql_db"] = $db_name;
       $_SESSION["sql_user"] = $user;
       $_SESSION["sql_host"] = $host;
       $_SESSION["sql_pwd"] = $pwd;
       $_SESSION["version"] = $versione;
       $_SESSION["spaceAvailable"] = $db_space;

       if(strlen(rtrim($gc)) > 0)
          $_SESSION["GoogleCalendar"] = $gc;
    }

    if($debug) {
    	  echo "Connecting to DB\n";
    	  echo "host = " . $host;
    	  echo "user = " . $user;
    	  echo "pwd = xxxxxx";
    	  echo "db = " . $db_name;
       }
   $conn = new mysqli($host, $user, $pwd, $db_name);

// Check connection
   if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
      }
  return $conn;
}
?>
