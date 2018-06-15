<?php
/******************************************
*
*	Ritorna il codice licenza
*
*	History: prima versione Aprile 2016
*
*******************************************/
require_once('../php/ritorna_path_to_key.php');
function ritorna_key() {
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//

	$debug=false;
	$index=0;
	$activationFile=ritorna_path_to_key();
	$key='undefined';
	$readFromFile=true;

   	if(session_status() == PHP_SESSION_NONE)      
       session_start();
       
   if(isset($_SESSION["actKey"])) { // Chiave software in sessione, non leggo il file
       $key = $_SESSION["actKey"];
       if($debug)
          echo "Key in sessione (" . $key .") NON accedo al file";
       $readFromFile=false;       
      }
      
    if($readFromFile) { // Dati chiave NON in sessione, leggo il file	
       if($debug)
          echo "Key NON in sessione, accedo al file '" . $activationFile . "'";

	    $keyFile    = file_get_contents($activationFile);
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
                          echo "Comment = " . $rows[$index];
                      $index++;
                      continue;
                    }

                 $row_data = explode("=", $data); 
                 $info[$row]['id']           = $row_data[0];
                 $info[$row]['value']      = $row_data[1];
                 
                 if($debug) {
                     echo $activationFile . " ID = " .$info[$row]['id']  .  " VALUE = " . $info[$row]['value'];
                    }
                 if($info[$row]['id'] == 'activationKey') // found key
                     $key = $info[$row]['value'];
                $index++;
                }
        if($key != 'undefined')
           $_SESSION["actKey"] = $key;
         }
                
   return($key); 
}
?>
