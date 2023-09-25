# Fattura-Elettronica-API-PHP-Client

Client PHP per utilizzare il servizio fattura-elettronica-api.it

Questa libreria PHP consente di inviare e ricevere le fatture elettroniche dal tuo gestionale al Sistema di Interscambio (SDI) dell'Agenzia delle Entrate, tramite il servizio https://fattura-elettronica-api.it

Il servizio consente la creazione e la ricezione delle fatture sia in formato XML, sia in forma semplificata, senza necessità di creare o leggere XML.

Per la creazione e la lettura delle fatture elettroniche in formato XML, può essere utilizzata questa libreria: https://github.com/clixclix2/FatturaElettronicaXML

## Utilizzo
La libreria è composta da un'unica classe: *FatturaElettronicaApiClient*

I metodi principali sono:
* ***invia()*** - Invia una fattura XML al SDI
* ***inviaConDati()*** - Invia una fattura al SDI, specificando i dati della fattura (destinatario, data, numero, righe del documento)
* ***ricevi()*** - Riceve le notifiche di consegna e le nuove fatture ricevute
* ***ottieniPDF()*** - Ritorna una versione PDF leggibile di una fattura elettronica
* ***ottieniAllegati()*** - Ritorna gli eventuali file allegati

### Inizializzazone
```php
$username = '......'; // Username e password forniti dal servizio
$password = '......';
$feac = new FatturaElettronicaApiClient($username, $password);
```
### Trasmissione di una fattura XML
```php
/**
 * Invia un documento (fattura, nota di credito, nota di debito) al SdI, trmamite Fattura Elettronica API
 * Il documento XML può essere inviato privo della sezione <DatiTrasmissione>. Se è presente, vengono utilizzati solo i dati CodiceDestinatario o PECDestinatario eventualmente presenti.
 * In caso di esito positivo, la fattura elettronica finale (quella effettivamente trasmessa al SDI) viene ritornata nel campo ['data']['sdi_fattura']
 * Se la fattura è nel formato FPA12 (verso la pubblica amministrazione)) e non viene trasmessa già firmata digitalmente, il documento andrà firmato digitalemnte manualmente tramite il pannello di controllo fattura-elettronica-api.it
 * @param string $xml Documento XML, charset UTF-8
 * @param string $codiceDestinatario
 * @param string $pecDestinatario
 * @param boolean $isTest Se true, il documento non viene inoltrato al SdI, viene generata una notifica di consegna di test e la fattura stessa verrà riproposta come ricezione di test
 * @return array Ritorna: ack=OK|KO - error=[eventuale errore] - data=array(sdi_identificativo, sdi_messaggio, sdi_fattura, sdi_nome_file)
 */
function invia($xml, $codiceDestinatario = NULL, $pecDestinatario = NULL, $isTest = false) {}
```
### Trasmissione di una fattura tramite i dati del documento
```php
/**
 * Invia un documento al SdI tramite Fattura Elettronica API, indicando i dati del documento
 * Questo metodo può gestire le casistiche di fatturazine più comuni. Per casistiche più complesse, è necessario generare l'XML completo ed utilizzare il metodo invia()
 * Per utilizzare questo metodo, è necessario aver inserito i propri dati aziendali completi nel pannello di controllo fattura-elettronica-api.it, nella sezine "Dati per generazione automatica fatture"
 * In caso di esito positivo, la fattura elettronica finale (quella effettivamente trasmessa al SDI) viene ritornata nel campo ['data']['sdi_fattura']
 * @param array $datiDestinatario PartitaIVA (opz.), CodiceFiscale (opz.), PEC (opz.), CodiceSDI (opz.), Denominazione, Indirizzo, CAP, Comune, Provincia (opz.)
 * @param array $datiDocumento tipo=FATT,NDC,NDD (opz. - default 'FATT'), Data, Numero, Causale (opz.)
 * @param array $righeDocumento Ogni riga è un array coi campi: Descrizione, PrezzoUnitario, Quantita (opz.), AliquotaIVA (opz. - default 22)
 * @param string $partitaIvaMittente In caso di account multi-azienda, specificare la partita iva del Cedente
 * @param bool $isTest Se true, il documento non viene inoltrato al SdI, viene generata una notifica di consegna di test e la fattura stessa verrà riproposta come ricezione di test
 * @return array
 */
function inviaConDati($datiDestinatario, $datiDocumento, $righeDocumento, $partitaIvaMittente = null, $isTest = false) {}
```
Per una guida completa ai dati che è possibile inserire in fattura, vedere il paragrafo più sotto "Guida ai Dati per creare una Fattura".
### Ricezione fatture e notifiche di consegna
```php
/**
 * Riceve tutti gli aggiornamenti dal SdI: documenti di fattura, note di credito/debito, ed esiti di consegna
 * In caso di ricezione di un documento, il campo 'ricezione' è valorizzato a 1 e sono presenti i campi dati_mittente, dati_documento e righe_documento, contenenti i dati significativi della fattura
 * Una volta ricevuto un documento, questo non viene più trasmesso alle successive invocazioni del metodo ricevi(), salvo andando sul pannello di controllo e reimpostando la spunta "Da leggere"
 * @param string $partitaIva Per ottenere solo i documenti relativi ad una partita iva, tra quelli associati all'utenza
 * @return array ack=OK|KO - error=[eventuale errore] - data=array di array coi campi: partita_iva, ricezione, sdi_identificativo, sdi_messaggio, sdi_nome_file, sdi_fattura, sdi_fattura_xml, sdi_data_aggiornamento, sdi_stato, dati_mittente, dati_documento, righe_documento
 */
function ricevi($partitaIva = NULL, $isTest = false) {}
```
### Ottenimento del file PDF che rappresenta una fattura elettronica
```php
/**
 * Ottiene la rappresentazione PDF di un documento ricevuto
 * @param string $sdiIdentificativo
 * @return array ack=OK|KO - error=[eventuale errore] - data= pdf=documento pdf codificato base-64
 */
function ottieniPDF($sdiIdentificativo) {}
```
### Ottenimento degli eventuali allegati di una fattura elettronica
```php
/**
 * Ottiene gli eventuali file allegati ad una fattura ricevuta
 * @param string $sdiIdentificativo
 * @return array ack=OK|KO - error=[eventuale errore] - data= array(descrizione, file codificato base64)
 */
function ottieniAllegati($sdiIdentificativo) {}
```
## Esempio di utilizzo
### Predisposizione della tabella sul database MySQL
```sql
CREATE TABLE `fatture_elettroniche` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_fattura` int(10) unsigned DEFAULT NULL, -- RIFERIMENTO ALLA FATTURA SUL PROPRIO DATABASE
  `id_fattura_elettronica_api` bigint(20) unsigned DEFAULT NULL, -- identificativo "fattura elettronica api"
  `sdi_identificativo` bigint(20) unsigned DEFAULT NULL,
  `sdi_stato` varchar(14) CHARACTER SET utf8 NOT NULL,
  `sdi_fattura` mediumtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `sdi_fattura_xml` mediumtext CHARACTER SET utf8 NOT NULL,
  `sdi_data_aggiornamento` datetime NOT NULL,
  `sdi_messaggio` text CHARACTER SET utf8 NOT NULL,
  `sdi_nome_file` varchar(50) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_fattura` (`id_fattura`),
  KEY `id_fattura_elettronica_api` (`id_fattura_elettronica_api`)
);
```
### Invio fattura XML
```php
$idFattura = [identificativo della fattura sul proprio database];
$fatturaXml = creaFatturaXml($idFattura); // Funzione - da creare - che estrae i dati della fattura dal proprio database e crea il formato XML nel formato <FatturaElettronica> (vedere esempi)

$codiceDestinatarioSDI = '[eventuale codice destinatario, se disponibile]';
$pecDestinatario = '[eventuale PEC del destinatario, se disponibile]';

$res = $feac->invia($fatturaXml, $codiceDestinatarioSDI, $pecDestinatario);

if ($res['ack'] == 'OK') {
	$stato = 'Inviato';
	$messaggio = $res['data']['sdi_messaggio'];
	$sdiIidentificativoDB = $res['data']['sdi_identificativo'] ? intval($res['data']['sdi_identificativo']) : 'NULL';
	$fatturaXml = $res['data']['sdi_fattura']; // La fattura elettronica xml finale
	$nomeFile = $res['data']['sdi_nome_file'];
	$idFeaDB = intval($res['data']['id']);
} else {
	$stato = 'Errore';
	$messaggio = $res['error'];
	$sdiIidentificativoDB = 'NULL';
	// $fatturaXml = $fatturaXml; // salviamo inalterata la fattura provvisoria
	$nomeFile = '';
	$idFeaDB = 'NULL';
}

$sqlInsertUpdate = "
	sdi_fattura = '" . $database->escape_string($fatturaXml) . "',
	sdi_nome_file = '" . $database->escape_string($nomeFile) . "',
	sdi_stato = '" .  $database->escape_string($stato) . "',
	sdi_messaggio = '" .  $database->escape_string($messaggio) . "',
	sdi_identificativo = {sdiIidentificativoDB},
	sdi_data_aggiornamento = now(),
	id_fattura = {$idFattura},
	id_fattura_elettronica_api = {$idFeaDB}
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
```

### Invio fattura tramite dati documento
```php
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
```

### Ricezione fatture ed aggiornamenti
```php
// Script da invocare periodicamente, per esempio ogni 30 minuti

$result = $feac->ricevi();

if ($result['ack'] == 'KO') {
	echo "Errore: " . $result['error'];
} else {
	echo "Elaborazione iniziata: " . date('Y-m-d H:i:s') . "\n<br>";
	foreach ($result['data'] as $arrDati) {
		if (!$arrDati['ricezione']) {
    
			// È un aggiornamento di un invio
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
			$sdiIdentificativoDB = $arrDati['sdi_identificativo'] ? intval($arrDati['sdi_identificativo']) : 'NULL';
			
			$database->query("
				UPDATE fatture_elettroniche
				SET sdi_stato = '{$sdiStato}',
					sdi_messaggio = '" . $database->escape_string($sdiMessaggio) . "',
					sdi_identificativo = {$sdiIdentificativoDB}
				WHERE id_fattura_elettronica_api = " . intval($arrDati['id']) . "
			");
			echo "Aggiorno Stato SDI {$arrDati['id']}/{$sdiIdentificativoDB} a {$sdiStato}\n<br>";
      
		} else {
    
			// È la ricezione di un documento
			
			$arrDati['sdi_fattura'] = base64_decode($arrDati['sdi_fattura']); // la fattura originale arriva codificata base64
			
			$sqlInsertUpdate = "
				sdi_identificativo = '" . $database->escape_string($arrDati['sdi_identificativo']) . "',
				sdi_stato = 'Ricevuto',
				sdi_fattura = '" . $database->escape_string($arrDati['sdi_fattura']) . "',
				sdi_fattura_xml = '" . $database->escape_string($arrDati['sdi_fattura_xml']) . "',
				sdi_data_aggiornamento = '" . $database->escape_string($arrDati['sdi_data_aggiornamento']) . "',
				sdi_messaggio = '" . $database->escape_string($arrDati['sdi_messaggio']) . "',
				sdi_nome_file = '" . $database->escape_string($arrDati['sdi_nome_file']) . "',
				id_fattura_elettronica_api = " . intval($arrDati['id']) . "
			";
			
			// verifichiamo se ce l'abbiamo già
			$res = $database->query("
				SELECT id
				FROM fatture_elettroniche
				WHERE id_fattura_elettronica_api = " . intval($arrDati['id']) . "
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
					WHERE id_fattura_elettronica_api = " . intval($arrDati['id']) . "
				");
			}
			echo "Inserisco fattura SDI {$arrDati['sdi_identificativo']}\n<br>";
			
		}
		
	}

	echo "Elaborazione termin.: " . date('Y-m-d H:i:s') . "\n<br>";
}

```

### Ricezione semplificata delle fatture
```php
$result = $feac->ricevi();
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
}
```
## Guida ai Dati per creare una Fattura
Il metodo inviaConDati() consente la creazione e l'invio di una fattura specificando i soli dati significativi del documento.
Di seguito una guida completa ai dati che è possibile specificare.
In linea generale, i dati da inserire sono queli attesi dal formato Fattura Elettronica, come descritto nelle Specifiche Tecniche dell'
Agendia delle Entrate.
* $datiDestinatario
  * PartitaIVA - Opzionale
  * CodiceFiscale - Opzionale, ma obbligatorio se è omessa la PartitaIVA
  * PEC - Opzionale, in alternativa a CodiceSDI
  * CodiceSDI - Opzionale solo se è stato inserito il parametro PEC. Altrimnti, se sconosciuto, inserire sette zeri: 0000000
  * Denominazione
  * Indirizzo
  * CAP
  * Comune
  * Provincia - Opzionale
* $datiDocumento
  * tipo - Opzionale - Valori ammissibili: FATT,NDC,NDD - Default: 'FATT'
  * Data - Data del documento - Formato: aaaa-mm-dd
  * Numero - Numero del documento
  * Causale - Opzionale - Causale generale del documento
  * ImportoRitenuta - Opzionale
  * AliquotaRitenuta - Opzionale - In caso sia specificato ImportoRitenuta, Default: 20
  * CausalePagamento - Opzionale - In caso sia specificato ImportoRitenuta, Default: 'A' (lavoro autonomo professionale)
  * DatiBollo - Opzionale
    * BolloVirtuale - valore ammesso: "SI"
    * ImportoBollo
  * DatiPagamento - Opzionale
    * CondizioniPagamento - Opzionale, default "TP02"
    * ModalitaPagamento - Opzionale, default bonifico "MP05" oppure contanti "MP01" se non è presente l'iban di incasso
    * DataScadenzaPagamento - Opzionale, formato aaaa-mm-gg
    * ImportoPagamento - Opzionale - default autocalcolato
* $righeDocumento
  * Array di righe con:
    * Descrizione
    * PrezzoUnitario
    * Quantita - Opzionale - Default: 1
    * AliquotaIVA - Opzionale - Default 22
    * Natura - Opzionale - Se AliquotaIVA = 0, Default: 'N1'
    * RiferimentoNormativo - Opzionale
    * ScontoMaggiorazione - Opzionale
      * Tipo - 'SC' oppure 'MG'
      * Importo - (oppure Percentuale)
    * CodiceArticolo - Opzionale
      * CodiceTipo - es: 'MPN' o 'EAN'
      * CodiceValore
    * UnitaMisura - Opzionale
    * EsigibilitaIVA - Opzionale - valori ammessi: I, D, S
    
