<?php
require_once('../php/unitalsi_include_common.php');
require_once("../php/ritorna_tessera_rinnovata.php");
require_once('../php/ricerca_comune.php');

if(!check_key())
   return; 
/****************************************************************************************************
*
*
*  Funzione che carica i dati del socio gia' inserito nel viaggio/pellegrinaggio
*
*  @file f_load_socio.php
*  @abstract carica i dati del socio in viaggio/pellegrinaggio
*  @author Luca Romano
*  @version 1.0
*  @time 2018-02-25
*  @history first release
*  
*  @first 1.0
*  @since 2018-02-25
*  @CompatibleAppVer All
*  @where Monza
*
*  input : connessione aperta al db
*             id socio
*            id attività dell'anno (tabella attivita_m) /viaggio o pellegrinaggio
*            tipo ('A' = attivita', 'V' = viaggio/pellegrinaggio)
*            data di fine pellegrinaggio (in caso di 'V' controllo data scadenza documento)
*            modifica dati (true/false, default false)
*
*
****************************************************************************************************/
function f_load_socio() {

//==== Variabili globali ====
$debug=false;
$fname=basename(__FILE__);
$defCharset = ritorna_charset();
$defCharsetFlags = ritorna_default_flags(); 
$date_format=ritorna_data_locale();
$sottosezione=ritorna_sottosezione_pertinenza();
}