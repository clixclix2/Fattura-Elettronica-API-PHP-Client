<?php

// Esempi di gestione multi-azienda

require_once __DIR__ . '/../FatturaElettronicaApiClient.class.php';

$username = '........'; //Username e password forniti dal servizio
$password = '........';
$feac = new FatturaElettronicaApiClient($username, $password);


$tipoTest = 'elenco';


if ($tipoTest == 'elenco') {

	$res = $feac->elencoAziende();

	if ($res['ack'] == 'KO') {
		var_dump($res);
	} else {
		foreach ($res['data'] as $azienda) {
			var_dump($azienda);
		}
	}
}


if ($tipoTest == 'aggiungi') {

	$ragioneSociale = 'Azienda Test Srl';
	$partitaIva = '12345678901';
	$codiceFiscale = '12345678901';
	
	$res = $feac->aggiungiAzienda(array(
		'ragione_sociale' => $ragioneSociale,
		'piva' => $partitaIva,
		'cfis' => $codiceFiscale
	));
	
	var_dump($res);
}


if ($tipoTest == 'rimuovi') {

	$partitaIva = '12345678901';
	
	$res = $feac->rimuoviAzienda($partitaIva);
	
	var_dump($res);
}


if ($tipoTest == 'documento') { // caricamento del documento di autorizzazione dell'azienda

	$partitaIva = '12345678901';
	$filePath = '/shares/_documenti/prezzi.pdf';
	$documento = file_get_contents($filePath);
	$nomeFile = basename($filePath);
	
	$res = $feac->inviaDocumentoAutorizzazione($partitaIva, $documento, $nomeFile);
	
	var_dump($res);
}

?>
