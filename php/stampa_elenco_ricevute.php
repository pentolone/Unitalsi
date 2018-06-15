<?php
/****************************************************************************************************
*
*  Stampa l'elenco delle ricevute nel periodo
*
*  @file stampa_elenco_ricevute.php
*  @abstract Genera file PDF con l'elenco delle ricevute emesse
*  @author Luca Romano
*  @version 1.1
*  @last 2017-09-29
*  @since 2017-03-03
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');

$debug=false;
$fname=basename(__FILE__);
// Include the main TCPDF library (search for installation path).
//require_once('/tcpdf/tcpdf.php');
define('EURO',chr(128));
define("MYPDF_MARGIN_TOP", 32);
define('BORDER_REQUIRED', 0); // 0 = No borders, 1 = Borders
define('NRICEVUTA_LENGTH', 33);
define('IMPORTO_LENGTH', 17);
define('MYPDF_MARGIN_LEFT',4);
define('MYPDF_MARGIN_RIGHT',4);
define('CELL_HEIGHT_P', 10); // Cell Height Portrait 
define('CELL_HEIGHT_L', 3); // Cell Height Landscape 

setlocale(LC_MONETARY, 'it_IT');

$root = realpath($_SERVER["DOCUMENT_ROOT"]);

include $root . "/tcpdf/tcpdf.php";

class MYPDF extends TCPDF {
	private $par;
	protected $subintestazione;
	protected $addH;
	
	function __construct( $par, $subIntestazione, $orientation, $unit, $format ) 
    {
        parent::__construct( $orientation, $unit, $format, false, 'UTF-8', false );

        $this->par = $par;
        $this->subintestazione = $subIntestazione;
        //...
    }
    public function setAdditionalH($additionalH)
    {
    	$this->addH = $additionalH;
    }
    // Page header
    public function Header() {
       $headerData = $this->getHeaderData();
       $this->SetX(8);
       $this->SetY(8);
       $this->Image('/images/Logo_UNITALSI.jpg', '', '', 20);
  
       $this->SetY(12);
       $this->SetFont('helvetica', 'B', '13px');
       $this->Cell(0, 15, $headerData['title'], 0, true, 'C', 0, '', 0, false, 'M', 'M');

       $this->SetFont('helvetica', 'B', '10px');
       $this->Cell(0, 15, $headerData['string'], 0, false, 'C', 0, '', 0, false, 'M', 'M');
                     
       $this->SetY(25);
       $this->SetFont('helvetica', 'BI', '10px');
       $this->Cell(0, 0, $this->subintestazione, 0, false, 'C', 0, '', 0, false, 'M', 'M');
       $this->SetY(30);
       $this->writeHTML('<hr><br>');
    }

    // Page footer
    public function Footer() {
    	  $foot = $this->par . ' Pagina ';
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', '', '6px');
        // Page number
        $this->Cell(0, 10, $foot . $this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

// Fine class TCPDF

$sott_app = ritorna_sottosezione_pertinenza();

$matches = array(); //create array
$pattern = '/[A-Za-z0-9-]*./'; //regex for pattern of first host part (i.e. www)
$pathFile="/img/";

$logo='Logo_UNITALSI.jpg';
$pdfH='';
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$date_format=ritorna_data_locale();
$index=0;
$html='';

// Check sessione
if(!check_key())
   return;

$idSoc = ritorna_societa_id();
$userid = session_check();
$sqlid_sottosezione=0;
$sqlanno=0;
$sqln_ricevuta=0;

// Dati risultanti dalla query
$sqlnome='';
$sqlindirizzo='';
$sqlcap='';
$sqlcitta='';
$sqldata_nascita='';
$sqlcf='';
$sqlluogo_nascita='';
$sqlid_categoria=0;
$sqlid_gruppo_par=0;
$sqlcausale='';
$sqlimporto=0;
$sqlutente='';
$sqln_unitalsi='';
$sqldata_ricevuta;
$sqln_ricevuta=0;
$totaleRicevute=0.00;

$sqlid_pellegrinaggio=0;
$sqlid_attivita=0;
$sqlid_socio=0;
$sqldal='';
$sqlal='';
$effettivo=false;
$tesseramento=false;
$catID=0;
$pagID=array();
$sqlorder=null;

$prn_format='L';

$outputCSV=false;
$target_CSV='../php/esporta_csv.php';

 $intestazioneCSV = array("NOME","INDIRIZZO", "CAP", "CITTA" , "LUOGO DI NASCITA","DATA DI NASCITA",
                                           "CODICE FISCALE", "ID GRUPPO","ID CATEGORIA", "CAUSALE" , "IMPORTO",
                                            "DATA RICEVUTA", "NUMERO RICEVUTA", "DATA DI ORDINAMNETO",
                                             "TESSERA UNITALSI", "GRUPPO", "TIPO PAGAMENTO","UTENTE");


$sqldes_pag='SCONOSCIUTA'; // descrizione pagamento
$sqldes_par='SCONOSCIUTO'; // descrizione gruppo parrocchiale

$str = $_SERVER["SERVER_NAME"]; // Get the server name

preg_match($pattern, $str, $matches); //find matching pattern, it should always work (i.e. www)
$extraDir =  rtrim($matches[0], '.');
   
//$logo = '../' . $extraDir . $pathFile . $logo;
$logo = '../images/' . $logo;
//echo $logo;

// Database connect
$conn = DB_connect();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

if(!$_POST) { // se NO post allora chiamata errata
   echo "Chiamata alla funzione errata";
   return;
}
else {
    $kv = array();
    foreach ($_POST as $key => $value) {

                   if($debug) {
                    	 echo $fname . ": KEY = " . $key . '<br>';
                    	 echo $fname . ": VALUE = " . $value . '<br>';
                    	 echo $fname . ": INDEX = " . $index . '<br><br>';
                    	}

    	            	 if($key == "id_pagamento") { // Array pagamenti
    	            	 	 $i_array=0;
      		              foreach($value as $value1) {
                                     $pagID[$i_array] = $value1; 	
                                     $i_array++;
                 	                }
      					     continue;
      					  }
      					 else
                       $kv[] = "$key=$value";


                   switch($key) {
      		                      case "id_sottosezione": // id_sottosezione
      				                        $sqlid_sottosezione = $value;
      				                        break;

      		                       case "id_pell": // ID pellegrinaggio
      					                      $sqlid_pellegrinaggio = $value;
      					                       break;

      		                       case "id_att": // ID attivita
      					                      $sqlid_attivita = $value;
      					                       break;

      		                       case "id_socio": // ID Socio
      					                      $sqlid_socio = $value;
      					                       break;

      		                       case "dal": // dal
      					                      $sqldal = $value;
      					                       break;

      		                       case "dal": // dal
      					                      $sqldal = $value;
      					                       break;

      		                       case "al": // al
      					                      $sqlal = $value;
      					                       break;

      		                       case "effettivo": // Solo soci effettivi
      					                      $effettivo = true;
      					                       break;

      		                       case "tesseramento": // Solo ricevute per rinnovo tessera
      					                      $tesseramento = true;
      					                       break;

      		                       case "id_categoria": // Solo una categoria
      					                      $catID = $value;
      					                       break;

      		                       case "id_pagamento": // Array tipo di pagamento se presente      		                                

      		                      case "order": // Ordinamento ricevute
      					                     $sqlorder = $value;
      				                        break;

      		                      case "prn_format": // Formato stampa
      					                     $prn_format = $value;
      				                        break;

      		                      case "CSV_x": // Richiesta Output CSV
      		                              $outputCSV=true;
      				                        break;
      	
      		                       default: // OPS!
      				                        //echo "UNKNOWN key = ".$key;
      				                        //return;
      				                        break;
      				              }  
                  $index++;
                }
            
        if($prn_format == 'P') { // Portrait
        	  $pageWidth = 210;
           $cellHeight = CELL_HEIGHT_P;
           }
            
        if($prn_format == 'L') { // Landscape
        	  $pageWidth = 300;
           $cellHeight = CELL_HEIGHT_L;
           }
        // Dipendentemente dalla larghezza pagina e dal numero di voci da stampare assegno lunghezza campi da stampare
        $dataWidth = round(($pageWidth - (NRICEVUTA_LENGTH+IMPORTO_LENGTH)) / 2) - (MYPDF_MARGIN_LEFT+MYPDF_MARGIN_RIGHT);
        //$dataWidth = round(($pageWidth - (NRICEVUTA_LENGTH+IMPORTO_LENGTH)) / 2) ;
               
         if($debug)
             echo "$fname: Spazio disponibile (in mm.) per i due campi = $dataWidth<br>";          

}

$subintestazione = 'Elenco ricevute dal ' . substr($sqldal,8,2) . '/' .
                                                                   substr($sqldal,5,2) . '/'.
                                                                   substr($sqldal,0,4) . ' al ' .
                                                                   substr($sqlal,8,2) . '/' .
                                                                   substr($sqlal,5,2) . '/'.
                                                                   substr($sqlal,0,4);

// SQL per ricevuta
$sql = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                          anagrafica.indirizzo,
                          anagrafica.cap,
                          citta,
                          luogo_nascita,
                          SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,                         
                          cf,
                          id_gruppo_par,
                          id_categoria, 
                          ricevute.causale,
                          ricevute.importo,
                          DATE_FORMAT(ricevute.data_ricevuta,'" . $date_format . "') data_ricevuta,
                          ricevute.n_ricevuta,
                          ricevute.data_ricevuta d_ord,
                          n_tessera_unitalsi,
                          gruppo_parrocchiale.descrizione desp,
                          tipo_pagamento.descrizione despag,
                          ricevute.utente,
                          ricevute.id_attpell,
                          ricevute.tipo,
                          anagrafica.id
            FROM    anagrafica
                          LEFT JOIN gruppo_parrocchiale
                          ON    anagrafica.id_gruppo_par = gruppo_parrocchiale.id,
                          ricevute
                          LEFT JOIN tipo_pagamento
                          ON    ricevute.id_pagamento = tipo_pagamento.id
             WHERE  anagrafica.id = ricevute.id_socio
             AND      ricevute.id_sottosezione = " . $sqlid_sottosezione .
           " AND     data_ricevuta BETWEEN '" . $sqldal . "' AND '" . $sqlal . "'";
           
if($sqlid_pellegrinaggio > 0) { // Richiesto singolo pellegrinaggio
    $sql .= " AND ricevute.tipo = 'V'
                   AND ricevute.id_attpell = $sqlid_pellegrinaggio";
   }
           
if($sqlid_attivita > 0) { // Richiesto singola attivita'
    $sql .= " AND ricevute.tipo = 'A'
                   AND ricevute.id_attpell = $sqlid_attivita";
   }
           
if($sqlid_socio > 0) { // Richiesto singolo socio
    $sql .= " AND ricevute.id_socio = $sqlid_socio";
   }

if($effettivo)
    $sql .= " AND anagrafica.socio_effettivo = 1";
           
if($tesseramento)
    $sql .= " AND ricevute.tessera = 1";
           
if($catID > 0)
    $sql .= " AND anagrafica.id_categoria = " . $catID;
           
if(!empty($pagID)) {
    $sql .= " AND ricevute.id_pagamento IN(";
    
    for($i=0; $i < count($pagID); $i++) {
    	    $sql .= $pagID[$i] . ", ";
          }
    $sql = rtrim($sql, ", ") . ")";
   }
             
$sql .= " ORDER BY YEAR(ricevute.data_ricevuta) DESC, 13 ". $sqlorder;

if($debug)
    echo "$fname SQL = $sql<br>";;

if($outputCSV) {
	echo "<form name='CSV' action='" . $target_CSV . "' method='POST'>";
	echo "<input type='hidden' name='sqlsearch' value='" . htmlspecialchars($sql, $defCharsetFlags, $defCharset) . "'>";
   echo "<input type='hidden' name='extras' value='" . htmlentities(serialize($intestazioneCSV)) . "'>";
                	
	echo "</form>";
   echo "<script>this.document.CSV.submit();</script>";
   return;
}
// SQL per società (sede centrale)
$sqlH = "SELECT CONCAT(sezione.nome,' - Sottosezione di ',sottosezione.nome) nome,
                            CONCAT(sottosezione.cap,' ', sottosezione.citta,
                                          ' (', province.sigla, ') - ', sottosezione.indirizzo,' - Tel. ',
                                          IFNULL(sottosezione.telefono,'*'), ' - Fax ', IFNULL(sottosezione.fax,'*')) indirizzo,
                                          sottosezione.id id
               FROM   sezione, sottosezione, province
               WHERE sottosezione.id_provincia = province.id
               AND     sezione.id = sottosezione.id_sezione
               AND     sottosezione.id = " . $sqlid_sottosezione;

// SQL per sottosezione
$sqlH1 = "SELECT CONCAT(sezione.nome,' - Sottosezione di ',sottosezione.nome) nome
                 FROM   sezione, sottosezione
                 WHERE  sezione.id = sottosezione.id_sezione
                 AND      sottosezione.id = " . $sqlid_sottosezione;

if($debug)
    echo $sqlH;
$result = $conn->query($sqlH);
$row = $result->fetch_assoc();
$pdfH = $row["nome"];
$pdfH1 = $row["indirizzo"];

$pdf = new MYPDF('Stampa elenco ricevute (Rev 1.0) Generato il: ' . date('d/m/Y H:i:s') ,$subintestazione, $prn_format, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Elenco ricevute (pronta per la stampa)');
$pdf->SetSubject('Elenco ricevute');
$pdf->SetKeywords('Ricevute');

// set default header data
$pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,64,0), array(0,64,128));
$pdf->setFooterData(array(0,64,0), array(0,64,128));

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(MYPDF_MARGIN_LEFT, MYPDF_MARGIN_TOP, MYPDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Add a page
// This method has several options, check the source code documentation for more information.

// Set font to helvetica

//$pdf->SetFont ('helvetica', '', '12px' , '', 'default', true );
//$pdf->Cell(80, 0, $pdfH, false, 'L', 2, 'C',false,'',0,false,'C','C');

// Accedo al DB
$result = $conn->query($sql);
$html = "<table><tr>";

$index=0;
while($row = $result->fetch_assoc()) {
	       if($index == 0)
		        $pdf->AddPage();
		    $index++;

	       $sqlnome = $row["nome"];
	       $sqlindirizzo = $row["indirizzo"];
	       $sqlcap = $row["cap"];
	       $sqlcitta = $row["citta"];
	       $sqldata_nascita = $row["dt_nas"];
	       $sqlcf = $row["cf"];
	       $sqlluogo_nascita = $row["luogo_nascita"];
	       $sqldes_pag = $row["despag"];
	       $sqldes_par = $row["desp"];
	       $sqlcausale = strtoupper($row["causale"]);
	       $sqlimporto = $row["importo"];
	       $sqldata_ricevuta = substr($row["data_ricevuta"],0,10);
	       $sqln_ricevuta = $row["n_ricevuta"];
	       $sqlutente = $row["utente"];
	       $sqlid_attpell = $row["id_attpell"];
	       $sqltipo = $row["tipo"];
	       
	       // Prendo la descrizione attivita'/Viaggio/Pellegrinaggio
	       
	       switch($sqltipo) {
	       	         case 'A': // Attivita'
	       	                      $sql = "SELECT attivita.descrizione,
	       	                                               attivita_m.anno
	       	                                   FROM   attivita,
	       	                                               attivita_detail,
	       	                                               attivita_m
	       	                                   WHERE attivita_detail.id_attpell = $sqlid_attpell
	       	                                   AND     attivita_detail.tipo = 'A'
	       	                                   AND     attivita_m.id = attivita_detail.id_attpell
	       	                                   AND     attivita_m.id_attivita = attivita.id";
	       	                                   break;

	       	         case 'V': // Viaggio/Pellegrinaggio
	       	                      $sql = "SELECT descrizione_pellegrinaggio.descrizione,
	       	                                                pellegrinaggi.anno
	       	                                   FROM   pellegrinaggi,
	       	                                               attivita_detail,
	       	                                               descrizione_pellegrinaggio
	       	                                   WHERE attivita_detail.id_attpell = $sqlid_attpell
	       	                                   AND     attivita_detail.tipo = 'V'
	       	                                   AND     pellegrinaggi.id = attivita_detail.id_attpell
	       	                                   AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id";
	       	                                   break;
	       	        }
	       	        
	       $sql_desattivita = "Nessuna associazione";
	       if($sqlid_attpell > 0) { // Accedo al DB
	          if($debug)
	              echo "$fname: SQL Attivit&agrave;/Viaggio = $sql<br>";
	          $r = $conn->query($sql);
	          if($r->num_rows > 0) {
	             $r1 = $r->fetch_assoc();
	             $sql_desattivita = $r1["descrizione"];
	             $anno = $r1["anno"];
	            }
	          $r->close();
	          
             if($anno != substr($sqldata_ricevuta,6,4)) { // Aggiungo anno alla descrizione
                 $sql_desattivita .= " ($anno)";
                }
	          
	          }

	       //$html = "<table><tr>";
	       $html .= "<td><p>" . $sqlnome . " ";
	       $html .= $sqlindirizzo. " " .$sqlcitta. " " . $sqlcf . " ";
	       $html .= "Nato il: " . substr($sqldata_nascita, 0, 10). " a : " . $sqlluogo_nascita. "<br>";
	       $html .=  "</tr><tr><td colspan=\"2\"><hr></td></tr><tr>";
	       $html .= "<td><p>pagamento: " . $sqldes_pag ."</td>";
	       $html .= "<td><p>gruppo: " . $sqldes_par ."</td></tr><tr>";
	       $html .= "<td><p>causale: " . $sqlcausale ."</td>";
	       $html .= "<td><p>ricevuta: " . $sqln_ricevuta ."</td></tr><tr>";
	       $html .= "<td><p>del: " . substr($sqldata_ricevuta,0,10) ."</td>";
	       $html .= "<td><p>importo: " . $sqlimporto ."</td></tr><tr>";
           // Cell($width,$height,'$text, $border=0, $newline=0,$align);

          $textToPrint =  '#' . sprintf('%05d', $sqln_ricevuta) . ' > ' . $sqldata_ricevuta;
          $pdf->SetFont ('helvetica', 'B', '8px' , '', 'default', true );
          $pdf->Cell(NRICEVUTA_LENGTH, $cellHeight, $textToPrint, BORDER_REQUIRED, 0, 'L', 0, '', 0, false, 'T', 'T');
          //$pdf->Multicell(33,0,, 0, 'L', false,0, 10,'', true);
         
          $textToPrint =   money_format('%(!n',$sqlimporto). ' ' . EURO;        
          $pdf->Cell(IMPORTO_LENGTH,$cellHeight, $textToPrint, BORDER_REQUIRED, 0, 'R', 0, '', 0, false, 'T', 'T');

          $cLength = 30;
          
          if($prn_format == 'L')
              $cLength = 50;
          $textToPrint =   $sql_desattivita . "\nCausale: " . substr($sqlcausale,0,$cLength);    
#MultiCell(w, h, txt, border = 0, align = 'J', fill = 0, ln = 1, x = '', y = '', reseth = true, stretch = 0, ishtml = false, autopadding = true, maxh = 0)
          $pdf->MultiCell($dataWidth,$cellHeight, $textToPrint, BORDER_REQUIRED, 'L', 0,0);

          $pdf->SetFont ('helvetica', 'N', '8px' , '', 'default', true );
          $textToPrint = $sqlnome . "  > " . $sqldes_par . " (" . $sqldes_pag . ")\nEmessa da: " . $sqlutente;
          $pdf->MultiCell(0,$cellHeight, $textToPrint, BORDER_REQUIRED, 'L', 0, 1);
          $totaleRicevute += $sqlimporto;

 }
 
   if($index > 0) { // Stampo totale
       $pdf->writeHTML('<HR><br>');
       $pdf->SetFont ('helvetica', 'B', '10px' , '', 'default', true );
         
       $pdf->Multicell((NRICEVUTA_LENGTH+IMPORTO_LENGTH),0, money_format('%(!n',$totaleRicevute). ' ' . EURO, 0, 'R', false,0, 10,'', true);
       $pdf->Multicell(0, 0, '( #' . sprintf('%05d', $index) . " )", 0, 'L', false,0, '','', true);
       if($debug)
          echo $html;
       else
   //$pdf->writeHTML($html, true, false, true, false, '');
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
          $pdf->Output('Elenco_ricevute.pdf', 'I');

      }
else {
	echo "<html><body>";
	echo "<h2>Nessun dato trovato con i parametri di ricerca</h2>";
	echo "</body></html>";
}
   $conn->close();
//============================================================+
// END OF FILE
//============================================================+
