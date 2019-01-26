<?php

require_once ("include/database/PearDatabase.php");

/**
 * Esempio di creazione di una Fattura Elettronica estraendo i dati dal database MySQL di VTiger 5.x
 * @param int $idFattura
 */
function creaFatturaXml($invoiceId) {
	
	global $adb;
	
	//
	// DATI DELL'AZIENDA (fissi)
	//
	$CedentePartitaIvaPaese = 'IT';
	$CedentePartitaIvaCodice = '[inserire partita iva]';
	$CedenteCodiceFiscale = '[inserire codice fiscale]';
	$CedenteDenominazione = 'Società di test S.r.l.';
	$CedenteRegimeFiscale = 'RF01'; // ordinario
	$CedenteIndirizzo = 'Via di test, 1';
	$CedenteCAP = '00100';
	$CedenteComune = 'Roma';
	$CedenteProvincia = 'RM';
	$CedenteNazione = 'IT';
	$CedenteREAUfficio = ''; // inserire codice ufficio REA, es: RM
	$CedenteREANumero = ''; // inserire numero REA, es: 1234567
	$CedenteREACapSoc = '10000.00'; // inserire capitale sociale dell'azienda
	$CedenteREASocioUnico = 'SM'; // SM = Soci Multipli
	$CedenteREALiquidazione = 'LN'; // LN = Non in liquidazione
	$CedenteIBAN = ''; // Inserire codice IBAN
	
	// estraiamo i dati e generiamo la fattura elettronica

	$queryHeader = "
		SELECT inv.*, invcf.*, co.*, cocf.*, coad.*, ac.*, acf.*, ba.*, sa.*
		FROM vtiger_invoice inv
		INNER JOIN vtiger_invoicecf invcf ON invcf.invoiceid = inv.invoiceid
		LEFT JOIN vtiger_account ac ON ac.accountid = inv.accountid
		LEFT JOIN vtiger_accountscf AS acf ON acf.accountid = inv.accountid
		LEFT JOIN vtiger_accountbillads ba ON ba.accountaddressid = inv.accountid
		LEFT JOIN vtiger_accountshipads sa ON sa.accountaddressid = inv.accountid
		LEFT JOIN vtiger_contactdetails co ON co.contactid = inv.contactid
		LEFT JOIN vtiger_contactscf AS cocf ON cocf.contactid = co.contactid
		LEFT JOIN vtiger_contactaddress coad ON coad.contactaddressid = co.contactid

		WHERE inv.invoiceid = {$invoiceId}
	";
	
	$resultHeader = $adb->query($queryHeader);
	$lineHeader = $adb->fetch_array($resultHeader);

	// var_dump($lineHeader);die();

	if ($lineHeader['accountid']) {
		$CessionarioPartitaIvaPaese = 'IT';
		$CessionarioPartitaIvaCodice = $lineHeader['crmv_vat_registration_number'];
		$CessionarioCodiceFiscale = $lineHeader['crmv_social_security_number'];
		$CessionarioDenominazione = $lineHeader['accountname'];
		$CessionarioIndirizzo = $lineHeader['bill_street'];
		$CessionarioCAP =$lineHeader['bill_code'];
		$CessionarioComune = $lineHeader['bill_city'];
		$CessionarioProvincia = strtoupper(substr($lineHeader['bill_state'], 0, 2));
		$CessionarioNazione = (!$lineHeader['bill_country'] ? 'IT' : strtoupper(substr($lineHeader['bill_country'], 0, 2)));
	} else {
		$CessionarioPartitaIvaPaese = 'IT';
		$CessionarioPartitaIvaCodice = $lineHeader['cf_XXX']; // eventuale campo custom creato sulla tabella vtiger_contactscf con la partita del cliente
		$CessionarioCodiceFiscale = $lineHeader['cf_YYY']; // eventuale campo custom creato sulla tabella vtiger_contactscf con il codice fiscale del cliente
		$CessionarioDenominazione = $lineHeader['lastname'] . ' ' . $lineHeader['firstname'];
		$CessionarioIndirizzo = ($lineHeader['otherstreet'] ? $lineHeader['otherstreet'] : $lineHeader['mailingstreet']);
		$CessionarioCAP = ($lineHeader['otherzip'] ? $lineHeader['otherzip'] : $lineHeader['mailingzip']);
		$CessionarioComune = ($lineHeader['othercity'] ? $lineHeader['othercity'] : $lineHeader['mailingcity']);
		$CessionarioProvincia = strtoupper(substr($lineHeader['otherstate'] ? $lineHeader['otherstate'] : $lineHeader['mailingstate'], 0, 2));
		$country = ($lineHeader['othercountry'] ? $lineHeader['othercountry'] : $lineHeader['mailingcountry']);
		$CessionarioNazione = (!$country ? 'IT' : strtoupper(substr($country, 0, 2)));
	}


	$doc = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
	<p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd" versione="FPR12">
		<FatturaElettronicaHeader>
			<CedentePrestatore/>
			<CessionarioCommittente/>
		</FatturaElettronicaHeader>
		<FatturaElettronicaBody>
			<DatiGenerali/>
			<DatiBeniServizi/>
			<DatiPagamento/>
		</FatturaElettronicaBody>
	</p:FatturaElettronica>
	');

	$CedentePrestatore = $doc->FatturaElettronicaHeader->CedentePrestatore;

	$DatiAnagrafici = $CedentePrestatore->addChild('DatiAnagrafici');
	$DatiAnagrafici->addChild('IdFiscaleIVA');
	$DatiAnagrafici->IdFiscaleIVA->addChild('IdPaese', $CedentePartitaIvaPaese);
	$DatiAnagrafici->IdFiscaleIVA->addChild('IdCodice', $CedentePartitaIvaCodice);
	$DatiAnagrafici->addChild('CodiceFiscale', $CedenteCodiceFiscale);
	$DatiAnagrafici->addChild('Anagrafica');
	$DatiAnagrafici->Anagrafica->addChild('Denominazione', $CedenteDenominazione);
	$DatiAnagrafici->addChild('RegimeFiscale', $CedenteRegimeFiscale);

	$Sede = $CedentePrestatore->addChild('Sede');
	$Sede->addChild('Indirizzo', $CedenteIndirizzo);
	$Sede->addChild('CAP', $CedenteCAP);
	$Sede->addChild('Comune', $CedenteComune);
	$Sede->addChild('Provincia', $CedenteProvincia);
	$Sede->addChild('Nazione', $CedenteNazione);

	$IscrizioneREA = $CedentePrestatore->addChild('IscrizioneREA');
	$IscrizioneREA->addChild('Ufficio', $CedenteREAUfficio);
	$IscrizioneREA->addChild('NumeroREA', $CedenteREANumero);
	$IscrizioneREA->addChild('CapitaleSociale', number_format($CedenteREACapSoc, 2, '.', ''));
	$IscrizioneREA->addChild('SocioUnico', $CedenteREASocioUnico);
	$IscrizioneREA->addChild('StatoLiquidazione', $CedenteREALiquidazione);


	$CessionarioCommittente = $doc->FatturaElettronicaHeader->CessionarioCommittente;

	$DatiAnagrafici = $CessionarioCommittente->addChild('DatiAnagrafici');
	if ($CessionarioPartitaIvaCodice) {
		$DatiAnagrafici->addChild('IdFiscaleIVA');
		$DatiAnagrafici->IdFiscaleIVA->addChild('IdPaese', $CessionarioPartitaIvaPaese);
		$DatiAnagrafici->IdFiscaleIVA->addChild('IdCodice', $CessionarioPartitaIvaCodice);
	}
	if ($CessionarioCodiceFiscale) {
		$DatiAnagrafici->addChild('CodiceFiscale', $CessionarioCodiceFiscale);
	}
	$DatiAnagrafici->addChild('Anagrafica');
	$DatiAnagrafici->Anagrafica->addChild('Denominazione', htmlspecialchars($CessionarioDenominazione, ENT_XML1));

	$Sede = $CessionarioCommittente->addChild('Sede');
	if ($CessionarioIndirizzo) {
		$Sede->addChild('Indirizzo', htmlspecialchars($CessionarioIndirizzo, ENT_XML1));
	}
	if ($CessionarioCAP) {
		$Sede->addChild('CAP', $CessionarioCAP);
	}
	if ($CessionarioComune) {
		$Sede->addChild('Comune', htmlspecialchars($CessionarioComune, ENT_XML1));
	}
	if ($CessionarioProvincia) {
		$Sede->addChild('Provincia', $CessionarioProvincia);
	}
	$Sede->addChild('Nazione', $CessionarioNazione);


	/**
			 * Tipo documento
			 * TD01 - Fattura
			 * TD02 - Acconto/Anticipo su Fattura
			 * TD03 - Acconto/Anticipo su parcella
			 * TD04 - Nota di Credito
			 * TD05 - Note di Debito
			 * TD06 - Parcella
			 * TD20 - Autofattura
			 */
	$tipoDocumento = 'TD01';
	if ($lineHeader['crmv_documenttype'] == 'Credit Note') {
		$tipoDocumento = 'TD04';
	}

	$DatiGenerali = $doc->FatturaElettronicaBody->DatiGenerali;

	$DatiGenerali->addChild('DatiGeneraliDocumento');
	$DatiGenerali->DatiGeneraliDocumento->addChild('TipoDocumento', $tipoDocumento);
	$DatiGenerali->DatiGeneraliDocumento->addChild('Divisa', 'EUR');
	$DatiGenerali->DatiGeneraliDocumento->addChild('Data', $lineHeader['invoicedate']);
	$DatiGenerali->DatiGeneraliDocumento->addChild('Numero', $lineHeader['invoice_no']);
	$DatiGenerali->DatiGeneraliDocumento->addChild('ImportoTotaleDocumento', number_format($lineHeader['total'], 2, '.', ''));
	if ($lineHeader['description']) {
		$DatiGenerali->DatiGeneraliDocumento->addChild('Causale', $lineHeader['description']);
	}


	$queryRighe = "
		SELECT i.*, p.*, pcf.*
		FROM vtiger_crmentity c
		INNER JOIN vtiger_invoice inv ON inv.invoiceid = c.crmid
		INNER JOIN vtiger_inventoryproductrel i ON inv.invoiceid = i.id
		INNER JOIN vtiger_products p ON p.productid = i.productid
		INNER JOIN vtiger_productcf pcf ON pcf.productid = p.productid
		WHERE c.deleted = 0 AND c.crmid = {$invoiceId}
		ORDER BY sequence_no
	";

	$resultRighe = $adb->query($queryRighe);


	$DatiBeniServizi = $doc->FatturaElettronicaBody->DatiBeniServizi;

	$arrRiepilogo = array();

	$numLinea = 0;
	while ($lineRighe = $adb->fetch_array($resultRighe)) {

		++$numLinea;
		$DettaglioLinee = $DatiBeniServizi->addChild('DettaglioLinee');

		$DettaglioLinee->addChild('NumeroLinea', $numLinea);

		$mpn = ($lineRighe['productcode'] ? $lineRighe['productcode'] : $lineRighe['mfr_part_no']);
		$descrizione = $lineRighe['productname'];

		if ($mpn) {
			$DettaglioLinee->addChild('CodiceArticolo');
			$DettaglioLinee->CodiceArticolo->addChild('CodiceTipo', 'MPN');
			$DettaglioLinee->CodiceArticolo->addChild('CodiceValore', htmlspecialchars($mpn, ENT_XML1));
		}

		// Se i prezzi sono inclusivi di IVA al 22%
		$piva = 22; // Percentuale IVA
		$prezzoUnitario = $lineRighe['listprice']/(1+$piva/100);
		
		/*
		// Se i prezzi sono al netto dell'iva
		$prezzoUnitario = $lineRighe['listprice'];
		$piva = $lineRighe['tax1']; // vedere in quale campo si trova l'aliquota iva applicata
		*/

		$DettaglioLinee->addChild('Descrizione', htmlspecialchars($descrizione, ENT_XML1));
		$DettaglioLinee->addChild('Quantita', $lineRighe['quantity']);
		$DettaglioLinee->addChild('PrezzoUnitario', number_format($prezzoUnitario, 2, '.', ''));
		$prezzo = ($lineRighe['quantity'] * $prezzoUnitario);
		$DettaglioLinee->addChild('PrezzoTotale', number_format($prezzo, 2, '.', ''));
		$DettaglioLinee->addChild('AliquotaIVA', number_format($piva, 2, '.', '')); // IVA FISSA, perché non abbiamo l'informazione
		
		if (!isset($arrRiepilogo[$piva])) {
			$arrRiepilogo[$piva] = 0.00;
		}
		$arrRiepilogo[$piva] += $prezzo;
	}

	foreach ($arrRiepilogo as $piva => $imponibile) {
		$DatiRiepilogo = $DatiBeniServizi->addChild('DatiRiepilogo');
		$DatiRiepilogo->addChild('AliquotaIVA', number_format($piva, 2, '.', ''));
		$DatiRiepilogo->addChild('ImponibileImporto', number_format($imponibile, 2, '.', ''));
		$DatiRiepilogo->addChild('Imposta', number_format($imponibile * $piva / 100, 2, '.', ''));
	}

	/**
	 * Tipi pagamento
	 * TP01 - a rate
	 * TP02 - completo
	 * TP03 - anticipo
	 */

	/**
	 * Modalità di pagamento
	 * MP01 - contanti
	 * MP05 - bonifico
	 */
	$DatiPagamento = $doc->FatturaElettronicaBody->DatiPagamento;
	$DatiPagamento->addChild('CondizioniPagamento', 'TP02');
	$DatiPagamento->addChild('DettaglioPagamento');
	$DatiPagamento->DettaglioPagamento->addChild('ModalitaPagamento', 'MP05');
	$DatiPagamento->DettaglioPagamento->addChild('ImportoPagamento', number_format($lineHeader['total'], 2, '.', ''));
	$DatiPagamento->DettaglioPagamento->addChild('IBAN', $CedenteIBAN);


	$xml = $doc->asXML();
	
	if (true) {
		// imbellettamento forzato
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml);
		$xml = $dom->saveXML();
	}
	
	$codiceDestinatarioSDI = NULL;
	if ($lineHeader['cf_XXXX']) { // Eventuale campo custom "Codice Destinatario SDI" sulla scheda Cliente
		$codiceDestinatarioSDI = $lineHeader['cf_XXXX'];
	}
	$pecDestinatario = NULL;
	if ($lineHeader['cf_YYYY']) { // Eventuale campo custom "PEC" sulla scheda Cliente
		$pecDestinatario = $lineHeader['cf_YYYY'];
	}


	$isTest = false;
	$res = $feac->invia($xml, $codiceDestinatarioSDI, $pecDestinatario, $isTest);

	$stato = ($res['ack'] == 'OK' ? 'Inviato' : 'Errore');
	$messaggio = ($res['ack'] == 'OK' ? $res['data']['sdi_messaggio'] : $res['error']);
	$identificativoSDI = ($res['ack'] == 'OK' ? $res['data']['sdi_identificativo'] : '');

	if (isset($res['data']) && isset($res['data']['sdi_fattura'])) {
		$xml = $res['data']['sdi_fattura']; // La fattura elettronica xml finale
	}

	$resFE = $adb->query("
		SELECT * FROM fatture_elettroniche WHERE id_invoice = {$invoiceId}
	");
	$lineFE = $adb->fetch_array($resFE, MYSQL_ASSOC);
	if ($lineFE) {
		$adb->query("
			UPDATE fatture_elettroniche
			SET sdi_fattura = '".  addslashes($xml)."',
				sdi_stato = '".  addslashes($stato)."',
				sdi_messaggio = '".  addslashes($messaggio)."',
				sdi_identificativo = '".  addslashes($identificativoSDI)."',
				sdi_data_aggiornamento = now()
			WHERE id_invoice = {$invoiceId}
		");
	} else {
		$adb->query("
			INSERT INTO fatture_elettroniche
			SET sdi_fattura = '".  addslashes($xml)."',
				sdi_stato = '".  addslashes($stato)."',
				sdi_messaggio = '".  addslashes($messaggio)."',
				sdi_identificativo = '".  addslashes($identificativoSDI)."',
				sdi_data_aggiornamento = now(),
				id_invoice = {$invoiceId}
		");
	}

	
}
