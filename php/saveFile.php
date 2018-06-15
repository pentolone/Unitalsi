<?php

  if ($_POST) { 
      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";

                     switch($key) {
      		                     case "fileName": // nome file
      					                    $fName = $value;
      					                    break;

      		                     case "content": // contenuti
      					                    $fContent = $value;
      					                    break;

                    }
                  }
   header("Content-Description: File Transfer");
   header('Expires: 0');
   header('Cache-Control: must-revalidate');
   header("Content-type: text/x-csv");
   header("Content-Disposition: attachment; filename=".$fName."");
      
    echo $fContent;
     }