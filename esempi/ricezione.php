<?php

// Ricezione fatture ed aggiornamenti di consegna/errore
// Script da invocare periodicamente, per esempio ogni 30 minuti

$username = '......'; // Username e password forniti dal servizio
$password = '......';
require_once __DIR__ . '/../FatturaElettronicaApiClient.class.php';
$feac = new FatturaElettronicaApiClient($username, $password);

$result = $feac->ricevi();

if ($result['ack'] == 'KO') {
	echo "Errore: " . $result['error'];
} else {
	echo "Elaborazione iniziata: " . date('Y-m-d H:i:s') . "\n<br>";
	foreach ($result['data'] as $arrDati) {
		if (!$arrDati['ricezione']) {
    
			// È un aggiornamento di un invio
			$sdiIdentificativo = $arrDati['sdi_identificativo'];
			if ($arrDati['sdi_stato'] == 'ERRO') {
				$sdiStato = 'Errore';
			} elseif ($arrDati['sdi_stato'] == 'CONS') {
				$sdiStato = 'Consegnato';
			} elseif ($arrDati['sdi_stato'] == 'NONC') {
				$sdiStato = 'Non Consegnato';
			} else {
				$sdiStato = $arrDati['sdi_stato'];
			}
			$sdiMessaggio = $arrDati['sdi_messaggio'];
			
			$database->query("
				UPDATE fatture_elettroniche
				SET sdi_stato = '{$sdiStato}',
					sdi_messaggio = '" . $database->escape_string($sdiMessaggio) . "'
				WHERE sdi_identificativo = '" . $database->escape_string($sdiIdentificativo) . "'
			");
			echo "Aggiorno Stato SDI {$sdiIdentificativo} a {$sdiStato}\n<br>";
      
		} else {
    
			// È la ricezione di un documento
			
			$sqlInsertUpdate = "
				sdi_identificativo = '" . $database->escape_string($arrDati['sdi_identificativo']) . "',
				sdi_stato = 'Ricevuto',
				sdi_fattura = '" . $database->escape_string($arrDati['sdi_fattura']) . "',
				sdi_fattura_xml = '" . $database->escape_string($arrDati['sdi_fattura_xml']) . "',
				sdi_data_aggiornamento = '" . $database->escape_string($arrDati['sdi_data_aggiornamento']) . "',
				sdi_messaggio = '" . $database->escape_string($arrDati['sdi_messaggio']) . "',
				sdi_nome_file = '" . $database->escape_string($arrDati['sdi_nome_file']) . "'
			";
			
			// verifichiamo se ce l'abbiamo già
			$res = $database->query("
				SELECT sdi_identificativo
				FROM fatture_elettroniche
				WHERE sdi_identificativo = '" . $database->escape_string($arrDati['sdi_identificativo']) . "'
			");
			if ($res->num_rows == 0) {
				$database->query("
					INSERT INTO fatture_elettroniche
					SET {$strInsertUpdate}
				");
			} else {
				// aggiornamento
				$database->query("
					UPDATE fatture_elettroniche
					SET {$strInsertUpdate}
					WHERE sdi_identificativo = '" . $database->escape_string($arrDati['sdi_identificativo']) . "'
				");
			}
			echo "Inserisco fattura SDI {$arrDati['sdi_identificativo']}\n<br>";
			
			// Opzionale: Otteniamo una rappresentazione PDF della fattura XML
			$resPDF = $feac->ottieniPDF($arrDati['sdi_identificativo']);
			if ($resPDF['ack'] == 'OK') {
				$fatturaXml = ($arrDati['sdi_fattura_xml'] ? $arrDati['sdi_fattura_xml'] : $arrDati['sdi_fattura']);
				$simpleXml = simplexml_load_string($fatturaXml);
				$nomeFornitore = (string)$simpleXml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Denominazione;
				if ($nomeFornitore == '') {
					$nomeFornitore = $simpleXml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Cognome . ' ' . $simpleXml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Nome;
				}
				$nomeFornitore = str_replace('/', '-', $nomeFornitore);
				$nomeFornitore = str_replace('\'', '', $nomeFornitore);
				$dataDocumento = (string)$simpleXml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Data;
				$numeroDocumento = (string)$simpleXml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Numero;
				$totaleDocumento = (string)$simpleXml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->ImportoTotaleDocumento;
				$tipoDocumento = (string)$simpleXml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->TipoDocumento;
				if ($tipoDocumento == 'TD01') {
					$tipoDocumento = 'Fattura';
				} elseif ($tipoDocumento == 'TD04') {
					$tipoDocumento = 'Nota di Credito';
				}

				$directoryPDF = '[percorso sul file system dove salvare il documento PDF]';
				$nomefile = $directoryPDF . '/' $nomeFornitore . ' - ' . $tipoDocumento . ' ' . $numeroDocumento.' del ' . $dataDocumento . ' Euro ' . $totaleDocumento . '.pdf';
				file_put_contents($nomefile, base64_decode($resPDF['data']['pdf']));

			}

		}
		
	}

	echo "Elaborazione termin.: " . date('Y-m-d H:i:s') . "\n<br>";
}
