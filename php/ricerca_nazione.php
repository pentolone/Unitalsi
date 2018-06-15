 <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
 <script type="text/javascript" >
 /*-------------------------------------------------
       Funzione che visualizza le nazioni durante la digitazione
       (almeno 2 caratteri)
       
 --------------------------------------------------*/
 function showNazioniMatch(field, div) {
    // Declare variables
    var input, filtro, ul, li, a, i, div;
    input = document.getElementById(field);
    filtro = input.value.toUpperCase();
    div = document.getElementById(div);
    ul = div.getElementsByTagName("ul")[0];
    li = ul.getElementsByTagName('li');
    
    div.style.display = "none";
    
    // Loop through all list items, and hide those who don't match the search query
    for (i = 0; i < li.length && filtro.length > 1; i++) {
        a = li[i];
        if (a.innerHTML.toUpperCase().indexOf(filtro) == 0) {
        	   if(div.style.display == "none") {
        	   	    div.style.display="block";
        	      }
       // alert(a.innerHTML);
            li[i].style.display = "";
        } else {
            li[i].style.display = "none";
        }
    }
}

/*-------------------------------------------------
       Funzione che valorizza i capi del comune selezinato
       
 --------------------------------------------------*/
 function comuneSelezionato(comune, field, divID) {
 	// Variables
 	var cap, codice_catastale, div, displ;
 	
   div = document.getElementById(divID);
 	cap = document.getElementById("cap");
 	codice_catastale = document.getElementById("codice_catastale");
 	id_provincia = document.getElementById("id_provincia");
 	displ = document.getElementById(field);
 	
 	// Hide List
 	div.style.display = "none";
 	
 	// Display comune
 	displ.value = comune.innerHTML;

   array = comune.getAttribute('value').split(';'); 	
 	if(cap && array[1] != "")
 	  cap.value = array[1];

 	if(codice_catastale && array[2] != "")
 	  codice_catastale.value = array[2];
 	 else {
 	 //	alert('no CAT');
 	 }

 	if(id_provincia && array[3] > 0)
 	  id_provincia.value = array[3];
 	 else {
 	 	//alert('no PROV');
 	 }
 }
  </script>
 <style>
 
 // Lista comuni
 #comuneInput {
    width: 100%; /* Full-width */
    font-size: 16px; /* Increase font-size */
    padding: 12px 20px 12px 40px; /* Add some padding */
    border: 1px solid #ddd; /* Add a grey border */
    margin-bottom: 12px; /* Add some space below the input */
}

div.comuneDIV {
    z-index: 100;
    width: 400px;
    display: none;
    background-color: white;
    position: absolute;
}
#comuneUL {
    /* Remove default list styling */
    list-style-type: none;
    white-space: nowrap;
    padding: 0;
    margin: 0;
}

#comuneUL li {
	display: block;
	}
#comuneUL li:hover {
    background-color: #eee; /* Add a hover effect to all links, except for headers */
    cursor: pointer;
    width: 400px;
}

/* Fine comune style */

</style>

<?php
/****************************************************************************************************
*
*  Funzione per la ricerca del comune
*
*  @file ricerca_comune.php
*  @abstract Seleziona il comune
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
function ricerca_comune($conn, $inputName, $inputValue, $divID, $inputClass='required', $requireCodCat=false) {
  $debug=false;
  $fname=basename(__FILE__);
  $index=0;
  $defCharset = ritorna_charset(); 
  $defCharsetFlags = ritorna_default_flags();
  $sott_app = ritorna_sottosezione_pertinenza();
  $multisottosezione = ritorna_multisottosezione();
  $date_format=ritorna_data_locale();
  // SQL select comuni
   $sqlSearch="SELECT comuni.id, CONCAT(comuni.nome,' (', province.sigla,')') n, comuni.codice_catastale,
                                     comuni.id_provincia, comuni.cap
                        FROM   comuni,
                                    province
                        WHERE comuni.id_provincia = province.id
                        ORDER BY 2";
   if($debug) {
       echo "$fname: SQL = $sqlSearch<BR>";
      }

  	$result = $conn->query($sqlSearch);
 
   echo "<input name='" . $inputName . "' class='" . $inputClass . "' id='" . $inputName . "' size='70' autocomplete='off' maxlength='100' onKeyUp='showComuniMatch(\"" . $inputName . "\", \"" . $divID . "\");'
             value='" . htmlentities($inputValue, $defCharsetFlags, $defCharset) . "' placeholder='Inserisci comune o parte del comune'>";
   echo "<div class='comuneDIV' id='" . $divID . "'>";
   echo "<ul id='comuneUL' style='width: 70;'>";
  	while($row = $result->fetch_assoc()) {
  		       $idP = $row["id_provincia"];
  		       $idCat = $row["codice_catastale"];
  		       $cAp = $row["cap"];
  		       if($requireCodCat) {
  		          $idP = 0;
  		          $cAp = "";
  		       }
  		       else 
  		          $idCat = '';
  		           
     		    echo "<li onClick='comuneSelezionato(this,\"" . $inputName . "\", \"" . $divID. "\");' name='comuniLI' style='display: none;' value=\"" . $row["id"] . ";" . $cAp . ";" . $idCat . ";" . $idP ."\">" . htmlentities($row["n"], $defCharsetFlags, $defCharset). "</li>";
     	      }
     	      
   echo "</ul>";
   echo "</div>";

}
