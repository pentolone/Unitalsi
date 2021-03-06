<LINK href="../css/menu_linguette.css" rel="stylesheet" type="text/css">
  <script>
  function setActiveDiv(i) {
 // alert("HERE");
    var arrayMese = document.getElementsByName("mese");
 //alert(arrayMese.length);
 //alert(i);
  //alert("HERE 1");
  arrayMese[i].style.display = "block";
  
  for(ix=0; ix < arrayMese.length; ix++) {
       if(ix != i) {
          arrayMese[ix].style.display = "none";
         }
         //alert(arrayMese[ix].style.display);
  
      }
  }

  
  </script>

<?php
/****************************************************************************************************
*
*  Funzione che visualizza l'occupazione nel periodo
*
*  @file f_occupazione.php
*  @abstract Visualizza l'occupazione della struttura selezionata
*  @author Luca Romano
*  @version 1.0
*  @time 2017-05-08
*  @history 1.0 prima versione
*  
*  @first 1.0
*  @since 2017-03-08
*  @CompatibleAppVer All
*  @where Monza
*
*
****************************************************************************************************/
function f_occupazione($conn, $id_pell_t) {
	require_once('../php/unitalsi_include_common.php');
	$debug=false;
	$defCharset = ritorna_charset(); 
   $defCharsetFlags = ritorna_default_flags(); 
   config_timezone();
   $current_user = ritorna_utente();
   $date_format=ritorna_data_locale();
   $fname=basename(__FILE__);
   
   $dataStart=null;
   $dataEnd=null;
   $id_pell = array($id_pell_t);
   
   $des_pell = new SplFixedArray(12);
   $color_pell=array("#DBA373", "#AF5D8A");
   
   $color_status=array("arrived" => "green", "stay" => "blue", "departing" => "red");
   $mesi=array("Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
                         "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre");

   $giorni_mese = new SplFixedArray(12); // Giorni mese

// Legenda da visualizzare
   $htmlLegenda = "<table style='border: solid 2px;'><tr><td colspan='2'>Legenda</td></tr><tr><td style='background-color: ";
   $htmlLegenda .= $color_status["arrived"] . ";padding-left: 10px;'>&nbsp;</td><td>Arrivo</td></tr><tr><td style='background-color: ";
   $htmlLegenda .= $color_status["stay"] . ";'>&nbsp;</td><td>Permanenza</td></tr><tr><td style='background-color: ";
   $htmlLegenda .= $color_status["departing"] . ";'>&nbsp;</td><td>Partenza</td></tr></table>";

// Mese da visualizzare
   $htmlMese = new SplFixedArray(12);

// Pellegrinaggi da visualizzare
   $htmlViaggi = new SplFixedArray(30);
   
   $htmlDays = array("<table>", "<table>", "<table>", "<table>",
                                  "<table>", "<table>", "<table>", "<table>",
                                  "<table>", "<table>", "<table>", "<table>");
	$index=0;
	// SQL pellegrinaggi
   $sqlselect_pellegrinaggio = "SELECT SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
                                                  SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al, 
                                                  pellegrinaggi.dal dal_order,
                                                  pellegrinaggi.id id_prn, descrizione_pellegrinaggio.descrizione desa,
                                                  (MONTH(pellegrinaggi.dal)-1) mDal,
                                                  (MONTH(pellegrinaggi.al)-1) mAl,
                                                  pellegrinaggi.al end
                                     FROM   descrizione_pellegrinaggio,
                                                  pellegrinaggi
                                      WHERE pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id AND
                                                  pellegrinaggi.id IN(";
    while($index < count($id_pell)) {
    	        $sqlselect_pellegrinaggio .= $id_pell[$index] . ", ";
    	        $index++;
    }
    $sqlselect_pellegrinaggio = rtrim($sqlselect_pellegrinaggio, ", ") . ")";
    $sqlselect_pellegrinaggio .= " ORDER BY 3, 4";
    
    if($debug)
       echo "$fname SQL = $sqlselect_pellegrinaggio<br>";

	// SQL partecipanti
   $sqlselect_partecipanti = "SELECT dal, al, (DAY(dal)-1) gStart, (DAY(al)-1) gEnd,
                                                           (MONTH(dal)-1) mStart, (MONTH(al)-1) mEnd, COUNT(*) ctr
                                               FROM   attivita_detail
                                               WHERE dal IS NOT NULL
                                               AND     al IS NOT NULL
                                               AND     id_attpell =";
                                             
                                                  
    // Ciclo per i viaggi selezionati
    $index=0;
    $result = $conn->query($sqlselect_pellegrinaggio);
    while($row = $result->fetch_assoc()) {

    	        $txt=$row["desa"];
    	        $txt .= " (" . $row["dal"] . " -> " . $row["al"]. ")";
    	        $txt=htmlentities($txt ,$defCharsetFlags, $defCharset);
    	        $meseDal = $row["mDal"];
    	        $meseAl = $row["mAl"];
    	        
    	        $dataEnd = $row["end"];
    	        $dataStart = $row["dal_order"];
    	        
    	        if(!$des_pell[$meseDal]) // Apro tabella
    	            $des_pell[$meseDal] = "<table>";
    	        
    	        if(!$des_pell[$meseAl]) // Apro tabella
    	            $des_pell[$meseAl] = "<table>";

    	        $des_pell[$meseDal] .= "<tr><td style='border: 2px solid " . $color_pell[$index] . ";'>" . $txt  . "</td></tr>";
    	        //echo $des_pell[$meseDal];

    	        if($meseAl != $meseDal) {
    	            $des_pell[$meseAl] .= "<tr><td>" . $txt  . "</td></tr>";
    	        	
    	           }
    	           
    	        if(!$giorni_mese[$meseDal][0]) {
    	            $lastDayOfMonth = date('t',strtotime($dataStart));
    	            $giorni_mese[$meseDal] = new SplFixedArray($lastDayOfMonth);
    	            
    	            }
    	        for($ix = 0; $ix < $lastDayOfMonth; $ix++) {
   	                 $giorni_mese[$meseDal][$ix] = array("arrived" => 0, "stay" => 0, "departing" => 0);
    	                 } 

    	        if(!$giorni_mese[$meseAl][0]) {
    	            $lastDayOfMonth = date('t',strtotime($dataEnd));
    	            
    	            $giorni_mese[$meseAl] = new SplFixedArray($lastDayOfMonth);
    	            }
    	        for($ix = 0; $ix < $lastDayOfMonth; $ix++) {
   	                 $giorni_mese[$meseAl][$ix] = array("arrived" => 0, "stay" => 0, "departing" => 0);
    	             } 
    	        
    	        if($debug)
    	            var_dump($giorni_mese);
    	        
    	        $sqlExec = $sqlselect_partecipanti . $row["id_prn"] . " GROUP BY 1,2,3,4,5,6";
              $resultP = $conn->query($sqlExec);
              
              if($debug)
                 echo "$fname: SQL = $sqlExec<br>";

              while($rowP = $resultP->fetch_assoc()) { // Ciclo per i partecipanti
                        $mStart = $rowP["mStart"];
                        $gStart = $rowP["gStart"];
                        
                        $element=$giorni_mese[$mStart][$gStart];
                        $element["arrived"] += $rowP["ctr"];
                        $giorni_mese[$mStart][$gStart] = $element;
                        $date1 = date_create($rowP["dal"]);
                        $date2 = date_create($rowP["al"]);
                        
                        $interval = date_diff($date1, $date2) ;
                        $elapsed = $interval->format('%a');
                        $dt = $rowP["dal"];
                        
                        for($stayCtr = 1; $stayCtr < $elapsed; $stayCtr++) { // Ciclo per i giorni di permanenza
                        	     
                              $dt = date('Y-m-d', strtotime($dt.' + 1 days'));
                              $mm = date("n",strtotime($dt)) - 1;
                              $gg = date("j",strtotime($dt)) - 1;

                              $element=$giorni_mese[$mm][$gg];
                              $element["stay"] += $rowP["ctr"];
                              $giorni_mese[$mm][$gg] = $element;
                             } // Fine ciclo for per i giorni
                             
                        $dt = date('Y-m-d', strtotime($dt.' + 1 days'));
                        $mm = date("n",strtotime($dt)) - 1;
                        $gg = date("j",strtotime($dt)) - 1;

                        $element=$giorni_mese[$mm][$gg];
                        $element["departing"] += $rowP["ctr"];
                        $giorni_mese[$mm][$gg] = $element;
                        
                       } // Fine ciclo partecipanti
               // Preparo disegno tabella arrivi/stay/partenza
              //$htmlDays[0] = 'table';
              for($ix = 0; $ix < count($giorni_mese); $ix++) { // ciclo per i mesi
                    for($ix1 = 0; $ix1 < count($giorni_mese[$ix]); $ix1++) { // ciclo per i giorni
              	           if($ix1 % 7 == 0) { // Visualizzo 7 giorni per riga
              	               if($ix1 > 0)
              	                  $htmlDays[$ix] .= '</tr>';
              	               $htmlDays[$ix] .= '<tr>';
              	             }
              	     
                           if($giorni_mese[$ix][$ix1]["arrived"] == 0 &&
                              $giorni_mese[$ix][$ix1]["stay"] == 0 &&
                              $giorni_mese[$ix][$ix1]["departing"] == 0) {                              
                	           $htmlDays[$ix] .= '<td style="border: 2px solid;">';
                	           $htmlDays[$ix] .= '<table>';
                	           $htmlDays[$ix] .= '<tr>';
                	           $htmlDays[$ix] .= '<td colspan="3" align="right">' . ($ix1+1) . '</td>';
                	           $htmlDays[$ix] .= '</tr>';
              	              $htmlDays[$ix] .= '<td style="padding-left: 20px; background-color: white;">0</td>';
              	              $htmlDays[$ix] .= '<td style="padding-left: 20px; background-color: white;">0</td>';
              	              $htmlDays[$ix] .= '<td style="padding-left: 20px; background-color: white;">0</td>';
              	              //$htmlDays[$ix] .= '<tr>';
                              }
                           else {
                	           $htmlDays[$ix] .= '<td style="border: 2px solid ' . $color_pell[$index] . ';">';
                	           $htmlDays[$ix] .= '<table>';
                	           $htmlDays[$ix] .= '<tr>';
                	           $htmlDays[$ix] .= '<td colspan="3" align="right">' . ($ix1+1) . '</td>';
                	           $htmlDays[$ix] .= '</tr>';

              	              $htmlDays[$ix] .= '<td style="font-weight: bold; font-size:18; padding-left: 20px; background-color: white;color:' . $color_status["arrived"] . ';">' . $giorni_mese[$ix][$ix1]["arrived"] . '</td>';
              	              $htmlDays[$ix] .= '<td style="font-weight: bold; font-size:18; padding-left: 20px; background-color: white;color:' . $color_status["stay"] . ';">' . $giorni_mese[$ix][$ix1]["stay"] . '</td>';
              	              $htmlDays[$ix] .= '<td style="font-weight: bold; font-size:18; padding-left: 20px; background-color: white;color:' . $color_status["departing"] . ';">' . $giorni_mese[$ix][$ix1]["departing"] . '</td>';
              	              //$htmlDays[$ix] .= '<tr>';
                          }
              	           $htmlDays[$ix] .= '</tr>';
              	           $htmlDays[$ix] .= '</table>';
              	           $htmlDays[$ix] .= '</td>';
             	           
                          }
              	      $htmlDays[$ix] .= '</tr></table>';
            	    
                   }
           if($debug)
                 var_dump($htmlDays);
    	     $index++;
           if($debug)
    	          var_dump($giorni_mese);
            } // Fine ciclo viaggi selezionati

    if($index > 0) { // Chiudo le tabelle
       $index = 0;
       while($index < count($des_pell)) {
       	       if($des_pell[$index])
       	           $des_pell[$index] .= "</table>";
       	       $index++;
       	       }
      }

//********** Output HTML *************
//    echo '<div class="occupazione">';

   //array_count_values($giorni_mese) ;
    if(count($giorni_mese) > 1) { // Disegno menù di scelta solo se viaggio su più mesi
        echo '<ul class="ling">';
    
        $ix1=0;
        for($ix = 0; $ix < count($giorni_mese); $ix++) { // Menu
    	       if($giorni_mese[$ix]) { // preparo il div
    	           echo '<li class="ling" onClick="setActiveDiv(' . $ix1 . ');"><a href="#">' . $mesi[$ix] . '</a></li>'; 
    	           $ix1++;
    	          }
             }
         echo '</ul>';
    }

    for($ix = 0; $ix < count($giorni_mese); $ix++) { // schermate mesi
    	    if($giorni_mese[$ix]) { // preparo il div
    	        echo '<div class="mese" name="mese" id="mm' . $ix . '">' ;
    	        echo '<p>';
    	        echo '<table>';

    	        echo '<tr>';
    	        echo '<td>'; // Legenda
    	        echo $htmlLegenda;  
    	        echo '</td>';
    	        
              echo '<td><table>';
              echo '<tr>';    	        
    	        echo '<td align="center">' . $mesi[$ix] . '</td>';
    	        echo '</tr>';
    	        
    	        echo '<tr>';
    	        echo '<td>'; // Viaggi/Pellegrinaggi
    	        
    	        for($ixv = 0; $ixv < count($des_pell); $ixv++) {
    	        	    if($des_pell[$ixv]) {
    	        	       echo $des_pell[$ixv];
    	        	       break; // forzo, tanto ne seleziono solo 1
    	        	    }
    	             }
    	        echo '</td>';
    	        echo '</tr>';
    	        echo '</table>';
    	        echo '</td>';
    	        echo '</tr>';

   	        
    	        echo '<tr>';
    	        echo '<td colspan="2">'; // Giorni
    	        echo $htmlDays[$ix];
    	        echo '</td>';
    	        echo '</tr>'; 
    	        
    	        echo '<tr>';
    	        echo '<td colspan="3" align="center"><input class="in_btn" type="button" value="Indietro"
    	                  onClick="window.location.href=\'../php/visualizza_occupazione.php\';"></td>';
    	        echo '</tr>';
    	        echo "</table>"; 
    	        
    	        echo '</p></div>';
    	       }
         }
//    echo '</div>';
    echo '<script>setActiveDiv(0);</script>';
    echo '</body></html>';

}
?>