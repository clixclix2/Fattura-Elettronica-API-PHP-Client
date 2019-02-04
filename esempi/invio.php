<?php

// Invio di una fattura elettronica

$username = '......'; // Username e password forniti dal servizio
$password = '......';
require_once __DIR__ . '/../FatturaElettronicaApiClient.class.php';
$feac = new FatturaElettronicaApiClient($username, $password);

$idFattura = '[identificativo della fattura sul proprio database]';
$fatturaXml = creaFatturaXml($idFattura); // Funzione - da creare - che estrae i dati della fattura dal proprio database e crea il formato XML nel formato <FatturaElettronica> (vedere esempi)

$codiceDestinatarioSDI = '[eventuale codice destinatario, se disponibile]';
$pecDestinatario = '[eventuale PEC del destinatario, se disponibile]';

$res = $feac->invia($fatturaXml, $codiceDestinatarioSDI, $pecDestinatario);

if ($res['ack'] == 'OK') {
	$stato = 'Inviato';
	$messaggio = $res['data']['sdi_messaggio'];
	$identificativoSDI = $res['data']['sdi_identificativo'];
	$fatturaXml = $res['data']['sdi_fattura']; // La fattura elettronica xml finale
	$nomeFile = $res['data']['sdi_nome_file'];
} else {
	$stato = 'Errore';
	$messaggio = $res['error'];
	$identificativoSDI = '';
	// $fatturaXml = $fatturaXml; // salviamo inalterata la fattura provvisoria
	$nomeFile = '';
}

$sqlInsertUpdate = "
	sdi_fattura = '" . $database->escape_string($fatturaXml) . "',
	sdi_nome_file = '" . $database->escape_string($nomeFile) . "',
	sdi_stato = '" .  $database->escape_string($stato) . "',
	sdi_messaggio = '" .  $database->escape_string($messaggio) . "',
	sdi_identificativo = '" .  $database->escape_string($identificativoSDI) . "',
	sdi_data_aggiornamento = now(),
	id_fattura = {$idFattura}
";

/** @var mysqli $database */

$lineFE = $database->query("
	SELECT * FROM fatture_elettroniche WHERE id_fattura = {$idFattura}
")->fetch_assoc();

if ($lineFE) { // aggiorniamo un record esistente
	$database->query("
		UPDATE fatture_elettroniche
		SET {$sqlInsertUpdate}
		WHERE id_fattura = {$idFattura}
	");
} else { // inseriamo un nuovo record
	$database->query("
		INSERT INTO fatture_elettroniche
		SET {$sqlInsertUpdate}
	");
}

?>
