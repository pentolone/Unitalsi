<?php
/****************************************************************************************************
*
*  Stampa le attività presenti nel DB
*
*  @file stampa_attivita.php
*  @abstract Genera file PDF con anagrafiche partecipanti all'attivita' presenti nel DB in formato A4
*  @author Luca Romano
*  @version 1.0
*  @since 2017-02-28
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');
require_once('../php/ritorna_tessera_rinnovata.php');
define('EURO',chr(128));
define("MYPDF_MARGIN_TOP", 37);
define('MYPDF_PAGE_ORIENTATION_LANDSCAPE', 'L');
define('MYPDF_PAGE_ORIENTATION_PORTRAIT', 'P');
define('MYPDF_MARGIN_LEFT',4);
define('MYPDF_MARGIN_RIGHT',4);
define('COGNOME_LENGTH', 90); // Lunghezza del cognome - nome
define('DNAS_LENGTH', 0); // Lunghezza del camp data di nascita
define('BORDER_REQUIRED', 0); // 0 = No borders, 1 = Borders

setlocale(LC_MONETARY, 'it_IT');
$debug=false;
$fname=basename(__FILE__);

// Include the main TCPDF library (search for installation path).
//require_once('/tcpdf/tcpdf.php');
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
include $root . "/tcpdf/tcpdf.php";

//======= Definizione classe TCPDF =========
class MYPDF extends TCPDF {
	private $par;
	protected $subintestazione;
	protected $addH;
	protected $desCosti=array();
	protected $printFilters=array();
   private $i=0;
	
	function __construct( $par, $subIntestazione, $orientation, $unit, $format ) 
    {
        parent::__construct( $orientation, $unit, $format, false, 'UTF-8', false );

        $this->par = $par;
        $this->subintestazione = $subIntestazione;
        //...
    }
    public function setAdditionalH($additionalH, $printFilters, $desCosti, $dataWidth)
    {
    	$this->addH = $additionalH;
    	$this->printFilters = $printFilters;
    	$this->desCosti = $desCosti;
    	$this->dataWidth = $dataWidth;
    }
    // Page header
    public function Header() {
       $headerData = $this->getHeaderData();
    	//$this->printFiletrs = $printFilters;

       	//echo(count($this->desCosti));
       
       $this->SetX(8);
       $this->SetY(8);
       $this->Image('/images/Logo_UNITALSI.jpg', '', '', 20);
  
       $this->SetY(12);
       $this->SetFont('helvetica', 'B', '13px');
       $this->Cell(0, 15, $headerData['title'], BORDER_REQUIRED, true, 'C', 0, '', 0, false, 'M', 'M');

       $this->SetFont('helvetica', 'B', '10px');
       $this->Cell(0, 15, $headerData['string'], BORDER_REQUIRED, false, 'C', 0, '', 0, false, 'M', 'M');

       $this->SetY(25);
       $this->Cell(0, 15, $this->addH, BORDER_REQUIRED, false, 'C', 0, '', 0, false, 'M', 'M');
       $this->SetY(30);
       
       // Se presenti stampo filtri di selezione
       $this->SetX(8);
       for($i=0;$i < count($this->printFilters);$i++) {
             $this->Cell(50, 0, $this->printFilters[$i], BORDER_REQUIRED, false, 'L', 0, '', 0, false, 'M', 'M');
       	    
       	  }
       
       $this->SetY(33);
       $this->writeHTML('<hr><br>');
       
       $this->SetY(35);
       $this->SetFont('helvetica', 'BI', '11px');
       $this->Cell(COGNOME_LENGTH, 0, $this->subintestazione[0], BORDER_REQUIRED, false, 'L', 0, '', 0, false, 'M', 'M');
       //$this->Cell(DNAS_LENGTH, 0, $this->subintestazione[1], BORDER_REQUIRED, false, 'C', 0, '', 0, false, 'M', 'M');
                     
       for($i=0;$i < count($this->desCosti);$i++) {
             $this->Cell($this->dataWidth, 0, $this->desCosti[$i], BORDER_REQUIRED, false, 'R', 0, '', 0, false, 'M', 'M');
            }
       $this->SetY(37);

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
//============== Fine class TCPDF ===================
$date_format=ritorna_data_locale();

$sqlid_sottosezione=0;
$pageWidth=0; // Larghezza pagina in mm.
$sqlid_attpell=0;
$sqlanno=0;
$sqltipo='-';
$sqlExec='';
$sqldatacheck=null;
$sqlid_categoria=0; // ID categoria socio
$sqlinsDal=null; // Intervallo di inserimento (Dal)
$sqlinsAl=null; // Intervallo di inserimento (Al)
$sqlRinnovo=-1; // Rinnovo tessera
$sqlNuova=-1; // Nuova tessera
$suddGroup=false; // Suddivisione per gruppo
$desGrp=null;

$sqlold_s=0;
$sqlold_p=0;
$sqlold_id_ana=0;
$fromSearch=false; // Verifica se richiamato da ricerca dati
$totViaggio= array(); // Totale spese viaggio
$totGruppo=array(); // Totale spese viaggio del gruppo
$desCosti=array();
$totalone=0;
$totaleViaggio=array();
$valoreRiduzione=0.00;
$valueToprint='';
$prn_format=MYPDF_PAGE_ORIENTATION_PORTRAIT;
$totalizza=false;
$iVC=0;
$i=0;
$ctrViaggio=0; // Totale partecipanti per viaggio
$ctrGruppo=0; // Totale partecipanti del gruppo
$sqlSearch='';
$table_name='unknown';

// Elenco dei filtri di selezione
$printFilters=array();
$ixPrintFilters=0;

$matches = array(); //create array
$pattern = '/[A-Za-z0-9-]*./'; //regex for pattern of first host part (i.e. www)
$pathFile="/img/";

$logo='Logo_UNITALSI.jpg';
$txtF=''; // Footer
$pdfH='';
$index=0;
$html='';
// Check sessione
if(!check_key())
   return;

$idSoc = ritorna_societa_id();
$userid = session_check();
$subInt=array('Nominativo', 'Data nascita');

$str = $_SERVER["SERVER_NAME"]; // Get the server name

preg_match($pattern, $str, $matches); //find matching pattern, it should always work (i.e. www)
$extraDir =  rtrim($matches[0], '.');
   
//$logo = '../' . $extraDir . $pathFile . $logo;

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
                  $kv[] = "$key=$value";
      
                   if($debug) {
                    	 echo $fname . ": KEY = " . $key . '<br>';
                    	 echo $fname . ": VALUE = " . $value . '<br>';
                    	 echo $fname . ": INDEX = " . $index . '<br><br>';
                    	}

                   switch($key) {
      		                      case "sqlsearch": // Stringa select ( da search))
      				                        $sqlSearch = $value;
      				                        $fromSearch=true;
                                        $txtF='Stampa risultati ricerca attività (Rev 1.0) Generato il: ' . date('d/m/Y H:i:s'); // Footer
      				                        break;

      		                      case "id_sottosezione": // id_sottosezione
      				                        $sqlid_sottosezione = $value;
      				                        break;

      		                      case "anno": // anno riferimento
      					                      $sqlanno = $value;
      				                          break;

      		                       case "tipo": // 'A' = Attivita 'V' = Viaggio/Pellegrinaggio
      					                      $sqltipo= $value;
      					                       break;

      		                       case "id_prn": // id attivita' / pellegrinaggio
      					                      $sqlid_attpell= $value;
      					                       break;

      		                       case "id_categoria": // id categoria socio
      					                      $sqlid_categoria = $value;
      					                       break;

      		                       case "id_gruppo": // id gruppo parrocchiale
      					                      $sqlid_gruppo= $value;
      					                       break;

      		                       case "insDal": // Intervallo (Dal) inserimento
      					                      $sqlinsDal= $value;
      					                      break;

      		                       case "insAl": // Intervallo (Al) inserimento
      					                      $sqlinsAl= $value;
      					                      break;

      		                       case "Rinnovo": // Rinnovo tessera
      					                      $sqlRinnovo= $value;
      					                      break;

      		                       case "Nuova": // Nuova tessera
      					                      $sqlNuova = $value;
      					                      break;

      		                       case "totalizza": // totalizza per gruppo parrocchiale
      					                      $totalizza= $value;
      					                       break;

      		                      case "prn_format": // formato stampa
      					                    $prn_format = $value;
      				                        break;

      		                       case "suddGroup": // Ordina per gruppo
      					                      $suddGroup= $value;
      					                       break;
 
      		                      case "searchTxt": // campo di ricerca (ignoring)
      				                        break;
      	
      		                       default: // OPS!
      				                        echo "$fname: UNKNOWN key = $key<br>";
      				                        return;
      				                        break;
      				              }  
                  $index++;
                }
                
         if($sqltipo == 'A') {
             $table_name = 'attivita_detail';
             $txtF = "Stampa partecipanti attivita' (Rev 1.0) Generato il: " . date('d/m/Y H:i:s'); 
            }    
         if($sqltipo == 'V') {
             $table_name = 'pellegrinaggi';
             $txtF = "Stampa saldo viaggio/pellegrinaggio (Rev 1.0) Generato il: " . date('d/m/Y H:i:s'); 
            }
            
        if($prn_format == 'P') { // Portrait
        	  $pageWidth = 210;
           }
            
        if($prn_format == 'L') { // Landscape
        	  $pageWidth = 300;
           }
	
}

if($suddGroup)
    $totalizza=false;

// SQL per sottosezione
$sqlsottosezione= "SELECT id, nome
                                 FROM   sottosezione
                                 WHERE id = $sqlid_sottosezione";
    
// SQL per anagrafica
$sqlA = "SELECT DISTINCT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                        attivita_detail.id_attpell,
                                        CONCAT(attivita.descrizione, ' ' ,IFNULL(CONCAT(' (' , attivita_m.descrizione,')'),'')) desa,
                                        attivita_m.dal dal_order,
                                        SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,
                                        SUBSTRING(DATE_FORMAT(attivita_m.dal,'" . $date_format ."'),1,10) dal,
                                        SUBSTRING(DATE_FORMAT(attivita_m.al,'" . $date_format ."'),1,10) al,
                                        attivita_m.dal dal_order,
                                        attivita.id,
                                        gruppo_parrocchiale.descrizione des_par,
                                        gruppo_parrocchiale.id id_par,
                                        anagrafica.id ida,
                                        attivita_detail.id_attpell idd,
                                        anagrafica.id_categoria
            FROM    anagrafica,
                          attivita_detail,
                          attivita_m,
                          gruppo_parrocchiale,
                          attivita
             WHERE anagrafica.id = attivita_detail.id_socio
             AND    attivita_detail.id_attpell = attivita_m.id
             AND    attivita_detail.tipo = 'A'
             AND     attivita_m.id_attivita = attivita.id
             AND    anagrafica.id_gruppo_par = gruppo_parrocchiale.id
             AND    attivita_detail.id_attpell = $sqlid_attpell";

$sqlAtot = "SELECT COUNT(DISTINCT anagrafica.id) ctr, 
                                gruppo_parrocchiale.descrizione nome,
                                attivita_detail.id_attpell,
                                CONCAT(attivita.descrizione, ' ' ,IFNULL(CONCAT(' (' , attivita_m.descrizione,')'),'')) desa,
                                attivita_m.dal dal_order,
                                '00/00/0000' dt_nas,
                                SUBSTRING(DATE_FORMAT(attivita_m.dal,'" . $date_format ."'),1,10) dal,
                                SUBSTRING(DATE_FORMAT(attivita_m.al,'" . $date_format ."'),1,10) al,
                                attivita_m.dal dal_order,
                                attivita.id,
                                gruppo_parrocchiale.id id_par,
                                MAX(attivita_detail.id) idd
                          FROM    anagrafica,
                          attivita_detail,
                          attivita_m,
                          attivita,
                          gruppo_parrocchiale
             WHERE anagrafica.id = attivita_detail.id_socio
             AND    attivita_detail.id_attpell = attivita_m.id
             AND    attivita_detail.tipo = 'A'
             AND    attivita_m.id_attivita = attivita.id
             AND    anagrafica.id_gruppo_par = gruppo_parrocchiale.id
             AND    attivita_detail.id_attpell = $sqlid_attpell";
//=== FINE SQL ATTIVITA'

// SQL per viaggio/pellegrinaggio
$sqlV = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                        pellegrinaggi.id id_attpell,
                                        CONCAT(descrizione_pellegrinaggio.descrizione, ' ' ,IFNULL(CONCAT(' (' , pellegrinaggi.descrizione,')'),'')) desa,
                                        SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,
                                        SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
                                        SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al,
                                        pellegrinaggi.dal dal_order,
                                        pellegrinaggi.id,
                                        gruppo_parrocchiale.descrizione des_par,
                                        gruppo_parrocchiale.id id_par,
                                        anagrafica.id ida,
                                        attivita_detail.id_attpell idd
                          FROM    anagrafica,
                          attivita_detail,
                          pellegrinaggi,
                          gruppo_parrocchiale,
                          descrizione_pellegrinaggio
             WHERE anagrafica.id = attivita_detail.id_socio
             AND     attivita_detail.id_attpell = pellegrinaggi.id
             AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
             AND     attivita_detail.tipo = 'V'
             AND    anagrafica.id_gruppo_par = gruppo_parrocchiale.id
             AND     pellegrinaggi.id = $sqlid_attpell";
 

$sqlVtot = "SELECT COUNT(*) ctr, 
                                gruppo_parrocchiale.descrizione nome,
                                        pellegrinaggi.id id_attpell,
                                        CONCAT(descrizione_pellegrinaggio.descrizione, ' ' ,IFNULL(CONCAT(' (' , pellegrinaggi.descrizione,')'),'')) desa,
                                        '00/00/0000' dt_nas,
                                        SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
                                        SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al,
                                        pellegrinaggi.dal dal_order,
                                        pellegrinaggi.id,
                                        0,
                                        gruppo_parrocchiale.id id_par,
                                        MAX(attivita_detail.id) idd
                          FROM    anagrafica,
                          attivita_detail,
                          gruppo_parrocchiale,
                          pellegrinaggi,
                          descrizione_pellegrinaggio
             WHERE anagrafica.id = attivita_detail.id_socio
             AND     attivita_detail.id_attpell = pellegrinaggi.id
             AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
             AND    attivita_detail.id_attpell = pellegrinaggi.id
             AND    anagrafica.id_gruppo_par = gruppo_parrocchiale.id
             AND     attivita_detail.tipo = 'V'
             AND     pellegrinaggi.id = $sqlid_attpell";
//=== FINE SQL Pellegrinaggi

// Richiesto uno specifico gruppo parrocchiale

if($sqlid_categoria > 0) { // Richiesta specifica categoria
    $sqlA .= " AND anagrafica.id_categoria = " . $sqlid_categoria;
    $sqlAtot .= " AND anagrafica.id_categoria = " . $sqlid_categoria;
    $sqlVtot .= " AND anagrafica.id_categoria = " . $sqlid_categoria;
    $sqlV .= " AND anagrafica.id_categoria = " . $sqlid_categoria;
    
    // Valorizzo filtro di selezione
    $r = $conn->query("SELECT categoria.descrizione
                                     FROM    categoria
                                     WHERE id = $sqlid_categoria");
                      
     $rs = $r->fetch_assoc();
     
     $printFilters[$ixPrintFilters] = "Categoria " . $rs["descrizione"];
     $ixPrintFilters++;
     $r->close();

    }

if($sqlid_gruppo > 0) {
    $sqlA .= " AND anagrafica.id_gruppo_par = " . $sqlid_gruppo;
    $sqlAtot .= " AND anagrafica.id_gruppo_par = " . $sqlid_gruppo;
    $sqlVtot .= " AND anagrafica.id_gruppo_par = " . $sqlid_gruppo;
    $sqlV .= " AND anagrafica.id_gruppo_par = " . $sqlid_gruppo;
    
    // Valorizzo filtro di selezione
    $r = $conn->query("SELECT gruppo_parrocchiale.descrizione
                                     FROM    gruppo_parrocchiale
                                     WHERE id = $sqlid_gruppo");
                       
     $rs = $r->fetch_assoc();
    
     $printFilters[$ixPrintFilters] = "Gruppo " . $rs["descrizione"];
     $ixPrintFilters++;
     $r->close();
     
    }

if($sqlinsDal && $sqltipo == 'A') { // Intervallo (Dal)
    $sqlA .= " AND attivita_detail.data >= '" . $sqlinsDal . " 00:00:00'";
    $sqlAtot .=  " AND attivita_detail.data >= '" . $sqlinsDal . " 00:00:00'";
    $sqlVtot .=  " AND attivita_detail.data >= '" . $sqlinsDal . " 00:00:00'";
    $sqlV .=  " AND attivita_detail.data >= '" . $sqlinsDal . " 00:00:00'";

//    $printFilters[$ixPrintFilters] = "Dal " . substr($sqlinsDal,8,2) . '/' .
   //                                                               substr($sqlinsDal,5,2) . '/'.
      //                                                            substr($sqlinsDal,0,4);
   //  $ixPrintFilters++;
    }

if($sqlinsAl && $sqltipo == 'A') { // Intervallo (Al)
    $sqlA .= " AND attivita_detail.data <= '" . $sqlinsAl . " 23:59:59'";
    $sqlAtot .=  " AND attivita_detail.data <= '" . $sqlinsAl . " 23:59:59'";
    $sqlVtot .=  " AND attivita_detail.data <= '" . $sqlinsAl . " 23:59:59'";
    $sqlV .=  " AND attivita_detail.data <= '" . $sqlinsAl . " 23:59:59'";

   // $printFilters[$ixPrintFilters] = "Al " . substr($sqlinsAl,8,2) . '/' .
      //                                                            substr($sqlinsAl,5,2) . '/'.
         //                                                         substr($sqlinsAl,0,4);
     // $ixPrintFilters++;
    
    }
//echo $sqlAtot;
//return;

if($sqltipo == 'A' && ($sqlRinnovo != -1 || $sqlNuova != -1)) { // Aggiungo rinnovo/Nuova
    $sqlA .= " AND attivita_detail.nuova IN($sqlRinnovo, $sqlNuova)";
    $sqlAtot .=  " AND attivita_detail.nuova IN($sqlRinnovo, $sqlNuova)";
    $sqlVtot .=  " AND attivita_detail.nuova IN($sqlRinnovo, $sqlNuova)";
    $sqlV .=  " AND attivita_detail.nuova IN($sqlRinnovo, $sqlNuova)";
    }

// SQL per intestazione
$sqlH = "SELECT CONCAT(sezione.nome,' - Sottosezione di ',sottosezione.nome) nome,
                            CONCAT(sottosezione.cap,' ', sottosezione.citta,
                                          ' (', province.sigla, ') - ', sottosezione.indirizzo,' - Tel. ',
                                          IFNULL(sottosezione.telefono,'*'), ' - Fax ', IFNULL(sottosezione.fax,'*')) indirizzo,
                                          sottosezione.id id
               FROM   sezione, sottosezione, province
               WHERE sottosezione.id_provincia = province.id
               AND     sezione.id = sottosezione.id_sezione
               AND sottosezione.id = $sqlid_sottosezione";

// SQL per voci di costo

if($sqltipo == 'A') {
   // Voci di costo Attivita'   
    $sqlVC = "SELECT costi.descrizione
                     FROM    costi
                     WHERE  id_parent = 0
                     AND      costi.id_attpell IN(SELECT id_attivita FROM attivita_m WHERE id = $sqlid_attpell)"; 

   $sqlExec = $sqlA;
    if($totalizza)
        $sqlExec = $sqlAtot;
}

if($sqltipo == 'V') {
   // Voci di costo Viaggio  
    $sqlVC = "SELECT costi.descrizione
                     FROM    costi
                     WHERE  id_parent = 0
                     AND      costi.id_attpell = $sqlid_attpell"; 
    $sqlExec = $sqlV;
    if($totalizza)
        $sqlExec = $sqlVtot;
}
             
if($totalizza) {
    $subInt[0] ="Gruppo";
    $subInt[1] = "  ";
   }

// create new PDF document
$pdf = new MYPDF($txtF, $subInt ,$prn_format, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

set_time_limit(120); // Questo per PDF che e' lento, la query e' una lippa!
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Elenco partecipanti (pronto per la stampa)');
$pdf->SetSubject('Anagrafica, Attivita/Viaggi');
$pdf->SetKeywords('Anagrafica, Attivita/Viaggi');

// set default header data
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

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font to helvetica
$pdf->SetFont ('helvetica', '', '12px' , '', 'default', true );

$index=0;

// Accedo al DB (dati sottosezione)
if($debug)
    echo "$fname SQL Header: $sqlH<br>";

$resultH = $conn->query($sqlH);
$rowH = $resultH->fetch_assoc();
$pdfH = $rowH["nome"];
$pdfH1 = $rowH["indirizzo"];
$resultH->close();

$sqlq = $sqlExec; // . " AND " . $table_name . ".id_sottosezione = " . $rowH["id"];
          
if($totalizza)
    $sqlq .= " GROUP BY 2,3,4,5,6,7,8,9,10,11";
    
 if($suddGroup) {
    $sqlq .= " ORDER BY des_par, id_par, nome";
    }
 else
    $sqlq .= " ORDER BY nome";
	       
if($fromSearch)
    $sqlq = $sqlSearch;
               
if($debug)
    echo "$fname: SQL Attivita/Viaggio = $sqlq<br>";  
    
 //  echo $sqlq;
  //  return;                 
$result = $conn->query($sqlq);

$oldid_grp=0;
// Accedo al DB (ciclo per le anagrafiche)
while($row = $result->fetch_assoc()) {
	       if($index == 0) {
	       	 $oldid_grp = $row["id_par"];
	       	 
	           $sqlExec = $sqlVC;

	           if($suddGroup) { // Memorizzo il gruppo
	              $desGrp = $row["des_par"];
                }
               
               if($debug)
                   echo "$fname: SQL Intestazione costi (solo se non chiedo raggruppamento) $sqlExec<br>";          

	            $iVC=0;
	            $resultVC = $conn->query($sqlExec);
	            
               if(!$totalizza) { 
                   while($rowVC = $resultVC->fetch_assoc()) { // Ciclo per le voci di costo principali
                             $desCosti[$iVC] = $rowVC["descrizione"];
                             $totViaggio[$iVC] = 0;
                             $totGruppo[$iVC] = 0;
                             $iVC++;
                            }
	                $resultVC->close();
                    }
                else {
                   $totViaggio[0] = 0;
                   $totGruppo[0] = 0;
                	$desCosti[0] = "Totale dovuto";
                	$iVC=1;
                   }
               $desCosti[$iVC] = 'Riduzione';
               $totViaggio[$iVC] = 0; // totale Riduzione
               $totGruppo[$iVC] = 0; // totale Riduzione gruppo

               $desCosti[$iVC+1] = 'Saldo';
               $totViaggio[$iVC+1] = 0; // totale Generale
               $totGruppo[$iVC+1] = 0; // totale Generale gruppo
               
               // Dipendentemente dalla larghezza pagina e dal numero di voci da stampare assegno lunghezza campi da stampare
               $dataWidth = round(($pageWidth - COGNOME_LENGTH) / ($iVC+2)) - (MYPDF_MARGIN_LEFT+MYPDF_MARGIN_RIGHT);
               //$dataWidth = round(($pageWidth - (COGNOME_LENGTH+DNAS_LENGTH)) / ($iVC+2)) - (MYPDF_MARGIN_LEFT+MYPDF_MARGIN_RIGHT);
               
               if($debug)
                   echo "$fname: Spazio disponibile (in mm.) per ogni voce di costo = $dataWidth<br>";          
                
               $html = "<table><tr>";
               $sqldatacheck = $row["dal_order"];
               
               if(!$sqlinsDal)
                   $sqlinsDal = $row["dal"];
               else 
               	   $sqlinsDal = date("d/m/Y", strtotime($sqlinsDal));
               
               if(!$sqlinsAl)
                   $sqlinsAl = $row["al"];
               else 
               	   $sqlinsAl = date("d/m/Y", strtotime($sqlinsAl));
// echo $printFilters[0];
               $pdf->setAdditionalH("Partecipanti - " .$row["desa"] . "       Periodo: " . $sqlinsDal . " - " . $sqlinsAl, $printFilters, $desCosti, $dataWidth);
               $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,0,0), array(0,0,0));
               // Aggiungo la pagina
               $pdf->AddPage();
             }	// Fine gestione intestazione
             
          // Se suddivisione per gruppo stampo totali e salto pagina
	       if($suddGroup && ($oldid_grp != $row["id_par"])) { // Cambio gruppo
	       // Totalizzo per gruppo
	           $pdf->writeHTML('<hr>');
	           // Stampo e azzero totali gruppo
              $pdf->SetFont ('helvetica', 'B', '10px' , '', true );
              $pdf->Cell((COGNOME_LENGTH+DNAS_LENGTH), '',"N. partecipanti " . $desGrp . " " . $ctrGruppo, BORDER_REQUIRED, 0, 'L');
	           for($i = 0; $i < count($totGruppo); $i++) {
                    $pdf->SetFont ('helvetica', 'B' , '9px' , '', '' );
	            
                    $pdf->Cell($dataWidth, '',money_format('%(!n',$totGruppo[$i]), BORDER_REQUIRED, 0, 'R');
	                 $totGruppo[$i] = 0;
                   }
	           $oldid_grp = $row["id_par"];
	           $desGrp = $row["des_par"];
	           $ctrGruppo=0;
	           $pdf->AddPage();

              }
	       $html = "<table><tr>";
	       $html .= "<td><p>" . $row["nome"] . " ";
	       $html .= "Nato il: " . substr($row["dt_nas"], 0, 10). "<br>";
	
	        if(($index % 2 == 0) && ($index > 0)) {
	             $html .=  "</tr><tr><td colspan=\"2\"><hr></td></tr><tr>";
                }

           if($totalizza) { // Stampo gruppo e numero partecipanti
               $ctrViaggio += $row["ctr"]; // Aggiorno totale partecipanti
               $textToPrint = substr($row["nome"], 0, 25) . " (#" . $row["ctr"] . ")";
               }
           else {
           	
               if($sqlold_id_ana != $row["ida"]) { // Stampo e memorizzo id anagrafica    
                   $sqlold_id_ana = $row["ida"];   
                   $tot=0; // Azzero totale socio       
                   $ctrViaggio += 1; // Aggiorno totale partecipanti
                   $ctrGruppo += 1; // Aggiorno totale partecipanti del gruppo
                   $textToPrint = substr($row["nome"], 0, 25);
                   // Aggiungo categoria (solo se non ho selezionato una specifica, solo per attività)
               
                   if($sqlid_categoria == 0 && $sqltipo == 'A') {
                  	   $r = $conn->query("SELECT descrizione FROM categoria
                 	                                     WHERE id = " . $row["id_categoria"]);
               	                                     
               	       $rs = $r->fetch_assoc();
               	   
               	       if($r->num_rows > 0)
               	          $textToPrint .= " (" . $rs["descrizione"] . ")";
               	      else
               	          $textToPrint .= " (N/A)";
               	      
               	      $r->close();
                     }
                      if(!ritorna_rinnovo($conn, $row["ida"], substr($sqldatacheck,0,4)))
                         $textToPrint .= " (*)";
                    }
              else {
              	$textToPrint = " ";
              }// Fine salto angrafica
            } 

           $pdf->SetFont ('helvetica', 'B', '9px' , '', true );
           // Cell($width,$height,'$text, $border=0, $newline=0,$align);
           $pdf->Cell(COGNOME_LENGTH, 0, $textToPrint, BORDER_REQUIRED, 0, 'L');

            if($totalizza)
                $textToPrint = " ";
            else
                $textToPrint = $row["dt_nas"];
 	                 
             if($totalizza) {	 // RIchiesta totalizzazione per gruppo             
	             $sql = "SELECT SUM((costi_detail.costo*costi_detail.qta)) costo,
	                                       SUM(costi_detail.valore) valore,
	                                       MAX(1) principale
	                          FROM   costi_detail,
                                          attivita_detail,";
	                                      
	              if($sqltipo == 'A') {	
	                  $sql .= "attivita_m	                               
     	                            WHERE costi_detail.id_parent = attivita_detail.id
	                               AND     attivita_detail.id_attpell = attivita_m.id 
	                               AND     attivita_m.id = $sqlid_attpell";
	               }

	              if($sqltipo == 'V') {	
	                  $sql .= "pellegrinaggi";
     	               $sql .= " WHERE costi_detail.id_parent = attivita_detail.id
	                                AND     attivita_detail.id_attpell = pellegrinaggi.id
	                                AND     pellegrinaggi.id =  $sqlid_attpell";
	               }
	                          
	              $sql .= " AND attivita_detail.id_socio IN(SELECT id FROM anagrafica
	                                                                            WHERE id_gruppo_par =" . $row["id_par"] . ")";
	              $sqlExec = $sql;
               } // Fine totalizza
            else {	 // Richiesta stampa singoli soci                 
                   if($sqltipo == 'A') {
	                    $sqlExec = "SELECT SUM(costi_detail.costo*costi_detail.qta) costo, MAX(costi_detail.id),
	                                          costi_detail.principale principale,costi_detail.id_costo,
	                                          SUM(costi_detail.valore) valore
	                            FROM     costi_detail
	                            WHERE    costi_detail.id_parent IN(SELECT attivita_detail.id FROM attivita_detail WHERE id_attpell= " . $row["idd"] .
	                          " AND id_socio = ". $row["ida"] . ") GROUP BY 3, 4 ORDER BY 2";
	                   }
	               else {
	                          
	               $sqlExec = "SELECT SUM(costi_detail.costo*costi_detail.qta) costo, costi_detail.id,
	                                          costi_detail.principale principale,
	                                          SUM(costi_detail.valore) valore
	                            FROM     costi_detail
	                            WHERE    costi_detail.id_parent IN(SELECT attivita_detail.id FROM attivita_detail WHERE id_attpell= " . $row["idd"] .
	                          " AND id_socio = ". $row["ida"] . ") GROUP BY 2 ORDER BY 2";
                   }
                }

             if($debug)
                 echo "$fname SQL costi = $sqlExec<br>";
                 
	          $resultC = $conn->query($sqlExec);
 
              $tot = 0;   
              $iVC = 0;                
              $valoreRiduzione=0;
              $valueToPrint='';
              $totVociCosto=0;
              $inx=0;

              while($rowC = $resultC->fetch_assoc()) {
                     	  if($rowC["principale"] == 1 && $inx > 0) {
                            // Stampo i costi principali 
                            $pdf->Cell($dataWidth, '',money_format('%(!n',$totVociCosto), BORDER_REQUIRED,0, 'R');
                            $totVociCosto = 0;
                            $iVC++;
                           }
                     	  $totVociCosto += $rowC["costo"];
                        $totViaggio[$iVC] += $rowC["costo"];
                        $totGruppo[$iVC] += $rowC["costo"];
                        $tot += $rowC["costo"];
                        $totalone += $rowC["costo"];
                        
                        $totViaggio[(count($totViaggio)-1)] += $rowC["costo"]; // Saldo generale
                        $totGruppo[(count($totGruppo)-1)] += $rowC["costo"]; // Saldo generale gruppo
                     	         
                     	   if($rowC["principale"] == 0)
                     	       continue; 

                          if($rowC["valore"] > 0) {
                              $valoreRiduzione = $rowC["valore"];
                              $totViaggio[(count($totViaggio)-2)] -= $valoreRiduzione; // Totale riduzione
                              $totGruppo[(count($totGruppo)-2)] -= $valoreRiduzione; // Totale riduzione gruppo
                              $valueToPrint = money_format('%(!n',$valoreRiduzione);
                             }
                          $inx++;
                        } // Fine ciclo while
              $iVC++;

              // Stampo l'ultimo dei costi principali              
              $pdf->Cell($dataWidth, '',money_format('%(!n',$totVociCosto), BORDER_REQUIRED, 0, 'R');
 
              // Controllo se emessa ricevuta
              $sql = "SELECT SUM(importo) pagato
                           FROM    ricevute
                           WHERE  id_attpell =  $sqlid_attpell
                           AND      ricevute.tipo = '". $sqltipo . "'";
        /*                   AND      id_socio   IN(SELECT attivita_detail.id_socio
                                                            FROM   attivita_detail
                                                            WHERE  id_attpell = $sqlid_attpell
                                                            AND      tipo         = '" . $sqltipo . "'"; */
               if(!$totalizza)
                   $sql .= " AND     ricevute.id_socio = " . $row["ida"];
               else
                    $sql .= " AND     ricevute.id_gruppo = " . $row["id_par"];

                if($debug)
                    echo "$fname SQL ricevute = $sql<br>";
                         
	             $resultRic = $conn->query($sql);
	             $rowRic = $resultRic->fetch_assoc();
	                  
	              $pagato=0;
	              if($rowRic["pagato"])
	                  $pagato = $rowRic["pagato"];
                 $tot -= ($valoreRiduzione+$pagato);

                 $totViaggio[(count($totViaggio)-1)] -= ($valoreRiduzione+$pagato); // Aggiorno  saldo generale
                 $totGruppo[(count($totGruppo)-1)] -= ($valoreRiduzione+$pagato); // Aggiorno  saldo generale gruppo
                 $totalone -= ($valoreRiduzione+$pagato);

                  // Stampo riduzione
                 $pdf->Cell($dataWidth, '',$valueToPrint, BORDER_REQUIRED,0, 'R');
                 $pdf->SetFont ('helvetica', 'B', '9px' , '', true );
                     
// Stampo il saldo a cambio anagrafica
                 $valueToPrint='';
                  if($tot != 0)
                      $valueToPrint = money_format('%(!n',$tot);
                   $pdf->Cell($dataWidth, '',$valueToPrint, BORDER_REQUIRED,1, 'R');
                     
                   $index++;
       } // Fine ciclo PRINCIPALE
$conn->close();

if($index > 0) { // Dati trovati
   if($debug)
      echo $html;
    else {
   //$pdf->writeHTML($html, true, false, true, false, '');
// ---------------------------------------------------------
// Se richiesto stampo totali ultimo gruppo
	    if($suddGroup) { // Cambio gruppo
	       // Totalizzo per gruppo
	        $pdf->writeHTML('<hr>');
          // Stampo e azzero totali gruppo
           $pdf->SetFont ('helvetica', 'B', '10px' , '', true );
           $pdf->Cell((COGNOME_LENGTH+DNAS_LENGTH), '',"N. partecipanti " . $desGrp . " " . $ctrGruppo, BORDER_REQUIRED, 0, 'L');
	        for($i = 0; $i < count($totGruppo); $i++) {
                 $pdf->SetFont ('helvetica', 'B' , '9px' , '', '' );
	            
                 $pdf->Cell($dataWidth, '',money_format('%(!n',$totGruppo[$i]), BORDER_REQUIRED, 0, 'R');
                }
            $pdf->Ln();
            $pdf->Ln();
            }

// Stampo i totali generali
        $pdf->writeHTML('<hr>');
        $pdf->SetFont ('helvetica', 'B', '10px' , '', true );
        $pdf->Cell((COGNOME_LENGTH+DNAS_LENGTH), '',"Totale N. partecipanti " . $ctrViaggio, BORDER_REQUIRED, 0, 'L');
        $pdf->SetFont ('helvetica', 'B' , '9px' , '', '' );
 
      for($iVC=0; $iVC < (count($desCosti)-1) ; $iVC++) {
             $pdf->Cell($dataWidth, '',money_format('%(!n',$totViaggio[$iVC]), BORDER_REQUIRED, 0, 'R');
            }
      $pdf->SetFont ('helvetica', 'B', '10px' , '', true );
      $pdf->Cell($dataWidth, '',money_format('%(!n',$totViaggio[$iVC]), BORDER_REQUIRED, 1, 'R');
      if(!$totalizza) {
          $pdf->SetFont ('helvetica', 'B', '8px' , '', true );
          $pdf->MultiCell(0, '',"(*) Tessera non ancora rinnovata", 0, 'L', false,1, '','', true);
         }
// Close and output PDF document
// This method has several options, check the source code documentation for more information.
      $pdf->Output('Stampa_viaggi.pdf', 'I');
     }
   }
else { // Nessun dato trovato
   echo "<h1>Nessun dato trovato coi parametri di selezione</h1>";
  }
//============================================================+
// END OF FILE
//============================================================+= $sqlid_attpell)"; 