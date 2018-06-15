<?php
/****************************************************************************************************
*
*  Funzione che esporta i dati in formato csv (EXCEL)
*
*  @file esporta_csv.php
*  @abstract Esporta i dati in formato csv
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-19
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-19
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
require_once('../php/unitalsi_include_common.php');
$fname=basename(__FILE__); 
$debug=false;
$csv_export = '';
$csv_filename = 'exportdata.csv';
$sqlSearch='';
$index=0;

$conn = DB_connect();

// Check connection
if ($conn->connect_error) 
    die("Connection failed: " . $conn->connect_error);

if(!$_POST) { // se NO post allora chiamata errata
   echo "Chiamata alla funzione errata";
   return;
}
else {
    $kv = array();
    foreach ($_POST as $key => $value) {
                  $kv[] = "$key=$value";
                   if($debug) {
                    	 echo $fname . ": KEY = " . $key . '<br>';
                    	 echo $fname . ": VALUE = " . $value . '<br>';
                    	 echo $fname . ": INDEX = " . $index . '<br><br>';
                    	}

                   switch($key) {
      		                      case "fileCSV": // File Output
      				                        $csv_filename = $value;
      				                        break;

      		                      case "sqlsearch": // Stringa select ( da search o altro )
      				                        $sqlSearch = $value;
      				                        break;

      		                      case "extras": // Stringa intestazione CSV
      				                        $intestazioneCSV = unserialize($value);
      				                        break;
      	
      		                       default: // OPS!
      				                        //echo "UNKNOWN key = ".$key;
      				                        //return;
      				                        break;
                              }
                  }
               $index++;
          }
          
    if($debug)
       echo "$fname (CSV) SQL = $sqlSearch<br>";;
    $result = $conn->query($sqlSearch);
    $field = mysqli_num_fields($result);
    
    if($debug)
        echo "$fname (CSV) Numero colonne = $field<br>";
// Intestazione CSV

    for($i=0; $i < count($intestazioneCSV); $i++)
          $csv_export .= '"' . $intestazioneCSV[$i] . '";';

    $csv_export .= '
';	

    while($row = $result->fetch_assoc()) {
  // create line with field values
              $finfo = $result->fetch_fields();
              //echo $row[0];

              foreach ($finfo as $val) {
                    $csv_export.= '"'.$row[$val->name].'";';
               }
              $csv_export.= '
';	
}

// Export the data and prompt a csv file for download
header("Content-Description: File Transfer");
header('Expires: 0');
header('Cache-Control: must-revalidate');
header("Content-type: text/x-csv");
header("Content-Disposition: attachment; filename=".$csv_filename."");
echo($csv_export);


?>