<?php
/******************************************
*
*       Ritorna la versione dell'applicativo
*
*       History: 
*                     1.3 evito accesso al file
*                     1.2 bug fix e licenza
*                     1.1 aggiunto search al menu
*                     1.0 prima versione Marzo 2016
*
*******************************************/
function ritorna_versione() {
	include_once('../php/ritorna_path_to_key.php');
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
	$debug=false;
	$index=0;
	$activationFile=ritorna_path_to_key();
	$versione='undefined';
   $readFromFile=true;

    if(session_status() == PHP_SESSION_NONE)      
        session_start();
        
    if(isset($_SESSION["version"])) { // Versione in sessione, non leggo il file
       $versione = $_SESSION["version"];
       if($debug)
          echo "Versione in sessione (" . $versione.") NON accedo al file";
       $readFromFile=false;       
      }


    if($readFromFile) { // Dati versione NON in sessione, leggo il file	
	
	    $keyFile    = file_get_contents($activationFile);
	    $rows = explode("\n", $keyFile);
	    $totRows=count($rows);
	    $totRows--;

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
                 $info[$row]['value']         = $row_data[1];
                 
                 if($debug) {
                     echo $activationFile . " ID = " .$info[$row]['id']  .  " VALUE = " . $info[$row]['value'];
                    }
                 if($info[$row]['id'] == 'version') // found version
                     $versione = $info[$row]['value'];
                 $index++;

                }
 //        $_SESSION["version"] = $versione;

        }
   return($versione);
}
?>
