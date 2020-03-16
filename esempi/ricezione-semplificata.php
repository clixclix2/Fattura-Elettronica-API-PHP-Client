<?php

// Ricezione semplificata di fatture
// Script da invocare periodicamente

$username = '......'; // Username e password forniti dal servizio
$password = '......';
require_once __DIR__ . '/../FatturaElettronicaApiClient.class.php';
$feac = new FatturaElettronicaApiClient($username, $password);

$result = $feac->ricevi();

if ($result['ack'] == 'OK') {

  foreach ($result['data'] as $arrDati) {
    if ($arrDati['ricezione']) {
      // È la ricezione di un documento

      $datiMitente = $arrDati['dati_mittente'];
      /*
      $datiMittente è un array che contiene i campi:
      - PartitaIVA
      - CodiceFiscale
      - Denominazione
      - Indirizzo
      - CAP
      - Comune
      - Provincia
      - Nazione
      */

      $datiDocumento = $arrDati['dati_mittente'];
      /*
      $datiDocumento è un array che contiene i campi:
      - Tipo (FATT|NDC|NDD)
      - Data (formato yyyy-mm-dd)
      - Numero
      - Causale
      - Totale
      */

      $righeDocumento = $arrDati['righe_mittente'];
      /*
      $righeDocumento è un array che contiene più array, ciascuno coi seguenti campi:
      - Descrizione
      - PrezzoUnitario
      - Quantita
      - AliquotaIVA
      */
    }
    
  } else {
    echo 'ERRORE: ' . $result['error'];
  }
}
