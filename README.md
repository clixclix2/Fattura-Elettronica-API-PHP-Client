# Fattura-Elettronica-API-PHP-Client
Client PHP per utilizzare il servizio fattura-elettronica-api.it

Questa libreria PHP consente di inviare e ricevere le fatture elettroniche dal tuo gestionale al Sistema di Interscambio (SDI) dell'Agenzia delle Entrate, tramite il servizio https://fattura-elettronica-api.it

Per la creazione e la lettura delle fatture elettroniche in formato XML, può essere utilizzata questa libreria: https://github.com/clixclix2/FatturaElettronicaXML

## Utilizzo
La libreria è composta da un'unica classe: *FatturaElettronicaApiClient* e da tre metodi: *invia*, *ricevi*, *ottieniPDF*

### Inizializzazone
```php
$username = '......'; // Username e password forniti dal servizio
$password = '......';
$feac = new FatturaElettronicaApiClient($username, $password);
```
### Trasmissione di una fattura
```php
/**
 * Invia un documento (fattura, nota di credito, nota di debito) a Fattura Elettronica API e successivamente a SDI, se non in modalità test
 * Il documento XML deve essere inviato privo della sezione <DatiTrasmissione>. In caso di esito positivo, la fattura elettronica finale (quella effettivamente trasmessa al SDI) viene ritornata nel campo ['data']['sdi_fattura']
 * @param string $xml Documento XML, charset UTF-8
 * @param string $codiceDestinatario
 * @param string $pecDestinatario
 * @param boolean $isTest
 * @return array Ritorna: ack=OK|KO - error=[eventuale errore] - data=array(sdi_identificativo, sdi_messaggio, sdi_fattura, sdi_nome_file)
 */
function invia($xml, $codiceDestinatario = NULL, $pecDestinatario = NULL, $isTest = false) {}
```
### Ricezione fatture ed aggiornamenti
```php
/**
 * Riceve tutti gli aggiornamenti dal SDI: documenti di fattura, note di credito/debito, ed esiti di consegna
 * @param string $partitaIva Per ottenere solo i documenti relativi ad una partita iva, tra quelli associati all'utenza
 * @return array ack=OK|KO - error=[eventuale errore] - data=array di array(partita_iva, ricezione, sdi_identificativo, sdi_messaggio, sdi_nome_file, sdi_fattura, sdi_fattura_xml, sdi_data_aggiornamento, sdi_stato)
 * 
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

## Esempio di utilizzo
### Predisposizione della tabella sul database MySQL
```sql
CREATE TABLE `fatture_elettroniche` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_fattura` int(11) DEFAULT NULL, -- RIFERIMENTO ALLA FATTURA SUL PROPRIO DATABASE
  `sdi_identificativo` varchar(36) CHARACTER SET utf8 NOT NULL,
  `sdi_stato` varchar(14) CHARACTER SET utf8 NOT NULL,
  `sdi_fattura` mediumtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `sdi_fattura_xml` mediumtext CHARACTER SET utf8 NOT NULL,
  `sdi_data_aggiornamento` datetime NOT NULL,
  `sdi_messaggio` text CHARACTER SET utf8 NOT NULL,
  `sdi_nome_file` varchar(50) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_fattura` (`id_fattura`)
);
```
### Invio fattura
```php
$idFattura = [identificativo della fattura sul proprio database];
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
			
		}
		
	}

	echo "Elaborazione termin.: " . date('Y-m-d H:i:s') . "\n<br>";
}

```
