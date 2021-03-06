 <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
 <style>
/* Stile pulsante ricerca */
.cercaSocioBtn {
    position: relative;
    padding: 6px 15px;
    left: -8px;
    border: 2px solid #207cca;
    background-color: #207cca;
    color: #fafafa;
}

.cercaSocioBtn:hover {
	background-color: #fafafa;
	color: #207cca;
}

/* The Modal (background) */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 100; /* Sit on top */
    padding-top: 100px; /* Location of the box */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgb(3,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}

/* Modal Content */
.modal-content {
    /*background-color: #fefefe;*/
    background-color: lightgray;
    margin: auto;
    padding: 0px;
    border: 1px solid #888;
    width: 40%;
    height: 50%;
    align-content: center;
    overflow-y: auto;
}

/* The Close Button */
.close {
    color: #aaaaaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: #000;
    text-decoration: none;
    cursor: pointer;
}
</style>

<?php
/****************************************************************************************************
*
*  Funzione per la ricerca del socio
*
*  @file ricerca_socio.php
*  @abstract Seleziona il socio
*  @author Luca Romano
*  @version 1.0
*  @time 2017-05-02
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-05-02
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
function ricerca_socio($formToSubmit=null, $valueToDisplay=null) {
  $debug=false;
  $fname=basename(__FILE__);
  $index=0;
  $defCharset = ritorna_charset(); 
  $defCharsetFlags = ritorna_default_flags();
  $sott_app = ritorna_sottosezione_pertinenza();
  $multisottosezione = ritorna_multisottosezione();
  $date_format=ritorna_data_locale();

  echo "<input name='ricercaSocio' class='search' id='sTxt' size='70' autocomplete='off' maxlength='100' placeholder='Inserisci cognome o parte del cognome'
             value='" . $valueToDisplay . "'>"; // Campo di ricerca
  
  if($formToSubmit) {
     echo "<input class='cercaSocioBtn' type='button' value='Ricerca...' onClick='document.getElementById(\"" . $formToSubmit ."\").submit();'>"; // Tasto di ricerca
     }
  else {
     echo "<input class='cercaSocioBtn' type='button' value='Ricerca...'>"; // Tasto di ricerca
  }
}

function risultati_ricercaS($conn, $formToSubmit, $sText = null, $id_sottosezione=0) {
  $debug=false;
  $fname=basename(__FILE__); 

  $defCharset = ritorna_charset(); 
  $defCharsetFlags = ritorna_default_flags();
  $sott_app = ritorna_sottosezione_pertinenza();
  $multisottosezione = ritorna_multisottosezione();
  $searchTxt = null;
  $searchTxt = $sText;
  $searchTxt .= "%";
  $sqlid_sottosezione=$id_sottosezione;
  
  if($sqlid_sottosezione == 0) {
  	   $sqlid_sottosezione = $sott_app;
     }
  
  $sqlSearch="SELECT anagrafica.id, CONCAT(anagrafica.cognome,' ',anagrafica.nome, '    Residente a: ',anagrafica.citta) n, 0
                        FROM   anagrafica
                        WHERE id_sottosezione = $sqlid_sottosezione
                        AND     LOWER(CONCAT(anagrafica.cognome, ' ', anagrafica.nome)) LIKE LOWER('" . $conn->real_escape_string($searchTxt) . "')";	  
   
// Carico i soci della altre sottosezioni
  $sqlSearch .= " UNION
          					  SELECT anagrafica.id, CONCAT(anagrafica.cognome,' ',anagrafica.nome,  '    Residente a: ',anagrafica.citta,
          					               ' (Sottosezione di ', sottosezione.nome,')') n, 1
                            FROM   anagrafica,
                                         sottosezione
                             WHERE LOWER(CONCAT(anagrafica.cognome, ' ', anagrafica.nome))  LIKE LOWER('" . $conn->real_escape_string($searchTxt) . "')
                             AND     id_sottosezione != $sqlid_sottosezione
                             AND     anagrafica.id_sottosezione = sottosezione.id
                             ORDER BY 3,2";
   if($debug) {
       echo "$fname: SQL = $sqlSearch<BR>";
      }

  	$result = $conn->query($sqlSearch);

   echo "<!-- The Modal -->";
   echo "<div id='myModal' class='modal'>";

    echo "<!-- Modal content -->";
    echo "<input type='hidden' name='id-s' value=0>";
    echo "<div class='modal-content'>";
    echo "<span class='close' onClick='togglePopUp(\"" . $formToSubmit . "\" );'>&times;</span>";
    echo "<p class='required'>";
    if($result->num_rows > 0) {
    	  //echo "Seleziona dalla lista</p>";
        echo "<select name='id-s' id='id-s' size=" . ($result->num_rows+1)." onChange='userSelected(\"" . $formToSubmit . "\");'>";
        echo "<optgroup label='Seleziona il nominativo dalla lista...'>";
        echo "<option value='0' style='display: none;' selected></option>";
        while($row = $result->fetch_assoc()) {
     		          echo "<option value=". $row["id"]. ">" . htmlentities($row["n"], $defCharsetFlags, $defCharset). "</option>";
     	            }
        echo "</optgroup></select></p>";
     	  }
     	else {
     		echo "<h1>Nessun dato trovato</h1>";
     	  }
    echo "</p>";
    echo "</div>";

    echo "</div>";
    echo "<script>togglePopUp();</script>";
}
?>
 <script type="text/javascript" >
 // Get the modal
var modal;
//alert(modal);

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on the button, open the modal 
function togglePopUp(f) {
   modal = document.getElementById('myModal');
  // alert(modal.style.display);
	if(modal.style.display == "none" || modal.style.display=='') {
	  //  alert("Ecco");
       modal.style.display = "block";
	   }
    else {
	    //alert("No buono");
       modal.style.display = "none";
       if(f)
         document.getElementById(f).submit();
      }   	
}

// When the user Select a name 
function userSelected(frm) {
	 togglePopUp();
	 document.getElementById(frm).submit();
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
	//alert("Ecco");
    //var modal = document.getElementById('myModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
 </script>

