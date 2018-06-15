<?php
/******************************************
*
*	Ritorna il path per la chiave di attivazione
*
*  - Path di default /lic
*  - 
*  - Se non trovato ritorna il path di  ../lic
*
*	History: prima versione Aprile 2016
*
*******************************************/
function ritorna_path_to_key() {
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
	
   $debug=false;
   $extraDir='';
   $pathFile="/lic/";
   $fileName='license.txt';
   
   $retFile = '';
   
   	$matches = array(); //create array
	$pattern = '/[A-Za-z0-9-]*./'; //regex for pattern of first host part (i.e. www)

   $activationFile = '../lic/license.txt';
   $curr_db=null;
   $str = $_SERVER["SERVER_NAME"]; // Get the server name
   
   if(preg_match($pattern, $str, $matches)) //find matching pattern, it should always work (i.e. www)
      $extraDir =  rtrim($matches[0], '.');
  
   $retFile .= '../' . $extraDir . $pathFile . $fileName;
  
  if(!file_exists($retFile))
      $retFiled = '../lic/license.txt';

   if($debug)
        echo "Activation file = " . $retFile . '<br>';
   return($retFile); 
}
?>