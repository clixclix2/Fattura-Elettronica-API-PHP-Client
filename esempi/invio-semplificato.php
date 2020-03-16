<?php

// Invio semplificato di una fattura elettronica tramite dati documento

$username = '......'; // Username e password forniti dal servizio
$password = '......';
require_once __DIR__ . '/../FatturaElettronicaApiClient.class.php';
$feac = new FatturaElettronicaApiClient($username, $password);

$datiDestinatario = [
	'PartitaIVA' => '12345678901',
	'CodiceFiscale' => '12345678901',
	'CodiceSDI' => '0000000',
	'Denominazione' => 'Azienda di test S.r.l.',
	'Indirizzo' => 'Via Col Vento, 1',
	'CAP' => '00100',
	'Comune' => 'Roma',
	'Provincia' => 'RM'
];

$datiDocumento = [
	'Data' => '2020-03-01',
	'Numero' => '123'
];

$righeDocumento = [
	[
		'Descrizione' => 'Installazione avvolgibile, manodopera (ore)',
		'PrezzoUnitario' => 50,
		'Quantita' => 3
	],
	[
		'Descrizione' => 'Avvolgibile in PVC',
		'PrezzoUnitario' => 100
	]
];


$username = '......'; // Username e password forniti dal servizio
$password = '......';
$feac = new FatturaElettronicaApiClient($username, $password);

$res = $feac->inviaConDati($datiDestinatario, $datiDocumento, $righeDocumento);

if ($res['ack'] == 'OK') {
	$identificativoSDI = $res['data']['sdi_identificativo'];
	$fatturaXml = $res['data']['sdi_fattura'];
}

?>
