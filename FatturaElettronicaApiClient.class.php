<?php

/**
 * Libreria Client PHP per utilizzare il servizio Fattura Elettronica API - https://fattura-elettronica-api.it
 * @author Itala Tecnologia Informatica S.r.l. - www.itala.net
 * @version 1.1
 */
class FatturaElettronicaApiClient {
	
	
	/**
	 * Indicare credenziali utente fornite da Fattura-Elettronica API
	 * @param string $username
	 * @param string $password
	 */
	function __construct($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}
	
	
	/**
	 * Invia un documento (fattura, nota di credito, nota di debito) a Fattura Elettronica API e successivamente a SDI, se non in modalità test
	 * Il documento XML deve essere inviato privo della sezione <DatiTrasmissione>. In caso di esito positivo, la fattura elettronica finale (quella effettivamente trasmessa al SDI) viene ritornata nel campo ['data']['sdi_fattura']
	 * @param string $xml Documento XML, charset UTF-8
	 * @param string $codiceDestinatario
	 * @param string $pecDestinatario
	 * @param boolean $isTest
	 * @return array Ritorna: ack=OK|KO - error=[eventuale errore] - data=array(sdi_identificativo, sdi_messaggio, sdi_fattura, sdi_nome_file)
	 */
	function invia($xml, $codiceDestinatario = NULL, $pecDestinatario = NULL, $isTest = false) {
		if (!$this->ensureAuthentication()) {
			return array(
				'ack' => 'KO',
				'error' => $this->authError
			);
		}
		$data = array(
			'action' => 'SEND',
			'token' => $this->authToken,
			'file' => $xml
		);
		if ($codiceDestinatario !== NULL) {
			$data['codice_destinatario'] = $codiceDestinatario;
		}
		if ($pecDestinatario !== NULL) {
			$data['pec_destinatario'] = $pecDestinatario;
		}
		if ($isTest) {
			$data['test'] = 1;
		}
		return $this->sendToEndpoint($data);
	}
	
	
	/**
	 * Riceve tutti gli agiornamenti dal SDI: documenti di fattura, note di credito/debito, ed esiti di consegna
	 * @param string $partitaIva Per ottenere solo i documenti relativi ad una partita iva, tra quelli associati all'utenza
	 * @return array ack=OK|KO - error=[eventuale errore] - data=array di array(sdi_identificativo, sdi_messaggio, sdi_nome_file, sdi_fattura, sdi_fattura_firmata, sdi_data_aggiornamento, sdi_stato)
	 * 
	 */
	function ricevi($partitaIva = NULL, $isTest = false) {
		if (!$this->ensureAuthentication()) {
			return array(
				'ack' => 'KO',
				'error' => $this->authError
			);
		}
		$data = array(
			'action' => 'RECV',
			'token' => $this->authToken
		);
		if ($partitaIva !== NULL) {
			$data['partita_iva'] = $partitaIva;
		}
		if ($isTest) {
			$data['test'] = 1;
		}
		return $this->sendToEndpoint($data);
	}
	
	
	/**
	 * Ottiene la rappresentazione PDF di un documento ricevuto
	 * @param string $sdiIdentificativo
	 * @return array ack=OK|KO - error=[eventuale errore] - data= pdf=documento pdf codificato base-64
	 */
	function ottieniPDF($sdiIdentificativo) {
		if (!$this->ensureAuthentication()) {
			return array(
				'ack' => 'KO',
				'error' => $this->authError
			);
		}
		$data = array(
			'action' => 'GETPDF',
			'token' => $this->authToken,
			'sdi_identificativo' => $sdiIdentificativo
		);
		return $this->sendToEndpoint($data);
	}
	
	
	/**
	 * Ottiene gli eventuali file allegati ad una fattura ricevuta
	 * @param string $sdiIdentificativo
	 * @return array ack=OK|KO - error=[eventuale errore] - data= array(descrizione, file codificato base64)
	 */
	function ottieniAllegati($sdiIdentificativo) {
		if (!$this->ensureAuthentication()) {
			return array(
				'ack' => 'KO',
				'error' => $this->authError
			);
		}
		$data = array(
			'action' => 'GETALLEGATI',
			'token' => $this->authToken,
			'sdi_identificativo' => $sdiIdentificativo
		);
		return $this->sendToEndpoint($data);
	}
	
	
	/**
	 * Assicura che si disponga del token di autenticazione
	 * Ritorna true se siamo autenticati, false se non siam riusciti ad autenticarci
	 * Come side-effect valorizza $this->authError in caso di errore e $this->authToken in caso di autenticazione avvenuta con successo
	 * @return boolean 
	 */
	private function ensureAuthentication() {
		if ($this->isAuthenticated) {
			return true;
		} else {
			$data = array(
				'action' => 'AUTH',
				'username' => $this->username,
				'password' => $this->password
			);
			$result = $this->sendToEndpoint($data);
			if ($result['ack'] == 'OK') {
				$this->isAuthenticated = true;
				$this->authToken = $result['token'];
				return true;
			} else {
				$this->authError = $result['error'];
				return false;
			}
		}
	}
	
	/**
	 * Invia i dati all'endpoint del servizio Fattura-Elettronica-Api.it
	 * @param type $data
	 * @return type
	 */
	private function sendToEndpoint($data) {
		$ch = curl_init($this->endpoint);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($result, true);
		if ($res === NULL) {
			return array(
				'ack' => 'KO',
				'error' => 'Errore output dal server: ' . $result
			);
		} else {
			return $res;
		}
	}
	
	
	private $endpoint = 'https://fattura-elettronica-api.it/ws1.0/';
	private $username = '';
	private $password = '';
	
	private $isAuthenticated = false;
	private $authToken = '';
	private $authError = '';
	
}
