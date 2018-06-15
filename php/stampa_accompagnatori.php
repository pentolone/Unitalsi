<?php
/****************************************************************************************************
*
*  Stampa i partecipanti al viaggio/pellegrinaggio
*
*  @file stampa_partecipanti.php
*  @abstract Genera file PDF con anagrafiche partecipanti all'attivita' presenti nel DB in formato Landscape
*  @author Luca Romano
*  @version 1.0
*  @since 2017-05-02
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');
require_once('../php/ritorna_tessera_rinnovata.php');

//require_once('../php/ritorna_tessera_rinnovata.php');
define('EURO',chr(128));
define("MYPDF_MARGIN_TOP", 35);
define('MYPDF_PAGE_ORIENTATION_LANDSCAPE', 'L');
define('MYPDF_PAGE_ORIENTATION_PORTRAIT', 'P');
setlocale(LC_MONETARY, 'it_IT');
$debug=false;
$fname=basename(__FILE__);

// Include the main TCPDF library (search for installation path).
//require_once('/tcpdf/tcpdf.php');
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
include $root . "/tcpdf/tcpdf.php";

class MYPDF extends TCPDF {
	private $par;
	protected $subintestazione=array();
	protected $subPos=array();
	protected $addH;
   private $i=0;
	
	function __construct( $par, $subIntestazione, $subPosizione, $orientation, $unit, $format ) 
    {
        parent::__construct( $orientation, $unit, $format, false, 'UTF-8', false );

        $this->par = $par;
        $this->subintestazione = $subIntestazione;
        $this->subPos = $subPosizione;
        //...
    }

    public function setAdditionalH($additionalH)
    {
    	$this->addH = $additionalH;
    }

    public function setAdditionalSub($subIntestazione)
    {
    	$this->subintestazione = $subIntestazione;
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
       $this->Cell(0, 15, $this->addH, 0, false, 'C', 0, '', 0, false, 'M', 'M');
       $this->SetY(30);
       $this->writeHTML('<hr><br>');
       
       $this->SetY(33);
       $this->SetFont('helvetica', 'BI', '10px');
                     
       for($i=0;$i < count($this->subintestazione);$i++) {
             $this->Cell($this->subPos[$i], 0, $this->subintestazione[$i], 0, false, 'L', 0, '', 0, false, 'M', 'M');
            }

       $this->SetY(35);
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
$date_format=ritorna_data_locale();

$sott_app = ritorna_sottosezione_pertinenza();

$sqlid_sottosezione=0;
$sqlid_attpell=0;
$sqlanno=0;
$sqltipo='-';
$sqlExec='';
$sqldatacheck=null;
$sqlsex='T';
$sqlid_categoria=0;
$descat=null;

$sqlold_s=0;
$sqlold_p=0;
$fromSearch=false; // Verifica se richiamato da ricerca dati
$totalone=0;
$valueToprint='';
$prn_format=MYPDF_PAGE_ORIENTATION_PORTRAIT;
$totalizza=false;
$i=0;
$ctrViaggio=0; // Totale partecipanti per viaggio
$sqlSearch='';
$table_name='unknown';

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

$userid = session_check();

// Campi intestazione
$subInt=array("Accompagnatore","Periodo","Accompagnato","Periodo");

// Lunghezza campi intestazione
$subPos=array(60, 40, 60, 40);

$str = $_SERVER["SERVER_NAME"]; // Get the server name

preg_match($pattern, $str, $matches); //find matching pattern, it should always work (i.e. www)
$extraDir =  rtrim($matches[0], '.');

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

      		                      case "id_sottosezione": // id_sottosezione
      				                        $sqlid_sottosezione = $value;
      				                        break;

      		                       case "id_prn": // id attivita' / pellegrinaggio
      					                      $sqlid_attpell= $value;
      					                       break;

      		                      case "anno": // anno riferimento
      					                    $sqlanno = $value;
      				                        break;

      		                      case "prn_format": // formato stampa
      					                    $prn_format = $value;
      				                        break;
      	
      		                       default: // OPS!
      				                        echo "UNKNOWN key = ".$key;
      				                        return;
      				                        break;
      				              }  
                  $index++;
                }
             $table_name = 'pellegrinaggi';
             $txtF = "Stampa elenco accompagnatori/accompagnati (Rev 1.0) Generato il: " . date('d/m/Y H:i:s'); 
	
}

// SQL per sottosezione
$sqlsottosezione= "SELECT id, nome
                                 FROM   sottosezione
                                 WHERE id = " . $sqlid_sottosezione;
    
// SQL per anagrafica/accompagnatore
$sql_ana = "SELECT DISTINCT anagrafica.id, CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                 SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) p_dal,
                                 SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) p_al,  
                                 SUBSTRING(DATE_FORMAT(accompagnatori.dal,'" . $date_format ."'),1,10) dal,
                                 SUBSTRING(DATE_FORMAT(accompagnatori.al,'" . $date_format ."'),1,10) al,  
                                 CONCAT(descrizione_pellegrinaggio.descrizione, ' ' ,IFNULL(CONCAT(' (' , pellegrinaggi.descrizione,')'),'')) desa
                    FROM    anagrafica,
                                 accompagnatori,
                                 pellegrinaggi,
                                 descrizione_pellegrinaggio
                     WHERE anagrafica.id = accompagnatori.id_accompagnatore
                     AND     accompagnatori.id_attpell = $sqlid_attpell
                     AND     pellegrinaggi.id = $sqlid_attpell
                     AND     descrizione_pellegrinaggio.id = pellegrinaggi.id_attpell
                     AND     accompagnatori.id_attpell = pellegrinaggi.id
                     ORDER BY 2";

// SQL per intestazione
$sqlH = "SELECT CONCAT(sezione.nome,' - Sottosezione di ',sottosezione.nome) nome,
                            CONCAT(sottosezione.cap,' ', sottosezione.citta,
                                          ' (', province.sigla, ') - ', sottosezione.indirizzo,' - Tel. ',
                                          IFNULL(sottosezione.telefono,'*'), ' - Fax ', IFNULL(sottosezione.fax,'*')) indirizzo,
                                          sottosezione.id id
               FROM   sezione, sottosezione, province
               WHERE sottosezione.id_provincia = province.id
               AND     sezione.id = sottosezione.id_sezione
               AND     sottosezione.id = $sqlid_sottosezione";
             
// create new PDF document
$pdf = new MYPDF($txtF, $subInt ,$subPos, $prn_format, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

set_time_limit(120); // Questo per PDF che e' lento, la query e' una lippa!
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Elenco accompagnatori/accompagnati (pronto per la stampa)');
$pdf->SetSubject('Anagrafica, Accompagnatori/Accompagnato');
$pdf->SetKeywords('Anagrafica, Accompagnatori/Accompagnatoi');

// set default header data
$pdf->setFooterData(array(0,64,0), array(0,64,128));

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, MYPDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font to helvetica

$pdf->SetFont ('helvetica', '', '12px' , '', 'default', true );
//$pdf->Cell(80, 0, $pdfH, false, 'L', 2, 'C',false,'',0,false,'C','C');

// Accedo al DB (ciclo per le sottosezioni)
if($debug)
    echo "$fname SQL Header: $sqlH<br>";
$resultH = $conn->query($sqlH);
$index=0;
$rowH = $resultH->fetch_assoc();

$pdfH = $rowH["nome"];
$pdfH1 = $rowH["indirizzo"];
//$pdf->AddPage();
               
if($debug)
    echo "$fname: SQL Anagrafica $sql_ana<br>";          
        
$index=0;

// Accedo al DB (ciclo per le anagrafiche degli accompagnatori)
$result = $conn->query($sql_ana);
while($row = $result->fetch_assoc()) {
	       if($index == 0) {
	           $txt = "Abbinamenti - " .$row["desa"] . "       Periodo: " . $row["p_dal"] . " - " . $row["p_al"];
              $pdf->setAdditionalH($txt);
              $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,0,0), array(0,0,0));
              $pdf->AddPage();
              $index++;
             }
             $textToPrint = $row["dal"] . " - " . $row["al"];
             $pdf->SetFont ('helvetica', 'B', '9px' , '', true );
             $pdf->MultiCell($subPos[0], 0,substr($row["nome"],0,27), 0, 'L', false,0, 10,$pdf->getY(), true);
                         
             $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
             $pdf->MultiCell($subPos[1], '',$textToPrint, 0, 'L', false,0, '','', true);
             
             // Seleziono gli accompagnati
             $sql = "SELECT anagrafica.id, CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                       SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                                       SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al 
                          FROM    anagrafica,
                                       attivita_detail,
                                       accompagnatori
                          WHERE  anagrafica.id = accompagnatori.id_accompagnato
                          AND      accompagnatori.id_attpell = $sqlid_attpell
                          AND      accompagnatori.id_attpell = attivita_detail.id_attpell
                          AND      accompagnatori.id_accompagnato = attivita_detail.id_socio
                          AND      accompagnatori.id_accompagnatore =  " . $row['id'] . "
                          ORDER BY 2";

             $res1 = $conn->query($sql);
             $x = $pdf->getX();
             while($row1 = $res1->fetch_assoc()) {
                       $pdf->SetFont ('helvetica', 'B', '9px' , '', true );
                       $pdf->MultiCell($subPos[2], 0,substr($row1["nome"], 0, 27), 0, 'L', false,0, $x,'', true);
                       
                       $textToPrint = $row1["dal"] . " - " . $row1["al"];
                       $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
                       $pdf->MultiCell($subPos[3], '',$textToPrint, 0, 'L', false,1, '','', true);
             }

 	     }

if($index > 0) {
	// Seleziona accompagnati NON presenti in tabella accompagnatori
	 $sql = "SELECT anagrafica.id, CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                              SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                              SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al,
                              0 c
                 FROM    anagrafica,
                              attivita_detail
                 WHERE  anagrafica.id = attivita_detail.id_socio
                 AND      attivita_detail.id_attpell = $sqlid_attpell
                 AND      attivita_detail.id_servizio NOT IN(SELECT id
                                                                                  FROM servizio
                                                                                  WHERE accompagna = 1)                 
                 AND      anagrafica.id NOT IN(SELECT id_accompagnato
                                                                FROM  accompagnatori
                                                                WHERE id_attpell = $sqlid_attpell)
                 UNION
                 SELECT anagrafica.id, CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                              SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                              SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al,
                              1 c
                 FROM    anagrafica,
                              attivita_detail,
                              accompagnatori
                 WHERE  anagrafica.id = accompagnatori.id_accompagnato
                 AND      accompagnatori.id_attpell = $sqlid_attpell
                 AND      attivita_detail.id_attpell = accompagnatori.id_attpell
                 AND      attivita_detail.id_socio = anagrafica.id
                 AND      accompagnatori.al < attivita_detail.al 
                 ORDER BY 2";

	 $i = 0;
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) {
    	        if($i==0) {
                  $pdf->setAdditionalSub(array("Non abbinati", "Periodo"));
                  $pdf->AddPage();
                  $i=1;
    	            }
             $textToPrint = $row["dal"] . " - " . $row["al"];
             $pdf->SetFont ('helvetica', 'B', '9px' , '', true );
             $pdf->MultiCell($subPos[0], 0,substr($row["nome"],0,27), 0, 'L', false,0, 10,$pdf->getY(), true);

             $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
             $pdf->MultiCell($subPos[1], 0,$textToPrint, 0, 'L', false,0, '','', true);

             if($row["c"] == 0)
                 $textToPrint = "Nessuna copertura";
             else                         
                 $textToPrint = "Copertura parziale";
             $pdf->MultiCell($subPos[2], 0,$textToPrint, 0, 'L', false,1, '','', true);
    	        
    }
   // Stampo eventuali soci NON accompagnati
   // ---------------------------------------------------------
// Stampo i totali
 $pdf->Output('Stampa_abbinamenti.pdf', 'I');
        $pdf->writeHTML('<hr>');
 //       $pdf->SetFont ('helvetica', 'B', '10px' , '', true );
  //      $pdf->MultiCell(80, 0,"N. partecipanti " . $ctrViaggio, 0, 'L', false,0, 10,'', true);
        
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