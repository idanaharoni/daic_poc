<?php

/**
 *
 * DAIC (Distributed Account Information Certification) PHP Client Class - v0.1
 *
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Idan Aharoni <aharoni@gmail.com>
  * @version 0.1
 */

define('DEFAULT_PORT', 1380);

class DAIC {

	# public static function to validate an account number

	public static function validate($domain, $accountNumber, $identifier = 'guest') {
		$results = self::getCertificationServer($domain);

		if (isset($results['error']) && $results['error'] != '') return $results;

		$results = self::queryServer($results['cs'], $domain, $accountNumber, $identifier);

		return $results;
	}

	# Get the certification server to query

	private function getCertificationServer($domain) {
		
		$dnsResults = dns_get_record('_daic.'.$domain, DNS_TXT);
		if (empty($dnsResults)) return ['error' => 'Provided domain does not exist or has no valid DAIC record'];

		if (strpos($dnsResults[0]['txt'], ';') === false) return ['error' => 'The contents of the DAIC record for the domain is ill-formatted'];
		
		$elements = explode(';', $dnsResults[0]['txt']);
		$settings = [];
		foreach ($elements as $curElement) {

			if (strpos($curElement, '=') === false) continue;

			$curElement = strtolower(trim($curElement));

			$key = trim(substr($curElement, 0, strpos($curElement, '=')));
			$value = trim(substr($curElement, strpos($curElement, '=') + 1));
			$settings[$key] = $value;
		}

		if (!isset($settings['v']) || $settings['v'] != 'daic' || !isset($settings['cs'])) return ['error' => 'The contents of the DAIC record for the domain is ill-formatted'];
		
		return $settings;
	}

	# Query the certification server

	private function queryServer($server, $domain, $accountNumber, $identifier) {

		# Extract port

		if (strpos($server, ':') !== false) {
			$port = substr($server, strpos($server, ':') + 1);
			$server = substr($server, 0, strpos($server, ':'));
		} else {
			$port = DEFAULT_PORT;
		}

		@$sock = fsockopen($server, $port);
		if ($sock === false) return ['error' => "Unable to connect to {$server}:{$port}"];

		$response = self::getResponse($sock);
    	if (substr($response, 0, 3) != '202') return ['error' => 'Server returned an unexpected response on initial connection: '.$response];

    	$result = self::writeSock($sock, 'HELO '.$identifier);
    	if ($result === false) return ['error' => 'Error while attempting to write to server'];

    	$response = self::getResponse($sock);
    	if (substr($response, 0, 3) != '200') return ['error' => 'Server returned an unexpected response on identification: '.$response];

    	$result = self::writeSock($sock, "QUERY ".$domain.':'.$accountNumber);
    	if ($result === false) return ['error' => 'Error while attempting to write to server'];

    	$response = self::getResponse($sock);
    	self::writeSock($sock, "QUIT");

    	if (substr($response, 0, 3) == '200') return ['response' => 'confirmed'];
    	else if (substr($response, 0, 3) == '201') return ['response' => 'unconfirmed'];
    	else if (substr($response, 0, 3) == '401') return ['response' => 'unauthorized'];
    	else if (substr($response, 0, 3) == '404') return ['response' => 'not found'];
    	else if (substr($response, 0, 3) == '500') return ['response' => 'invalid format'];
    	else if (substr($response, 0, 3) == '501') return ['response' => 'invalid domain'];
    	else if (substr($response, 0, 3) == '502') return ['response' => 'invalid account'];
    	else return ['error' => 'Server returned an unexpected response on initial connection: '.$response];

	}

	private function getResponse($sock) {
		$serverResponse = '';

		while ($serverResponse == '') {
			$serverResponse = fgets($sock, 256);
		}

		return trim($serverResponse);
	}

	private function writeSock($sock, $content) {
		try {
			$content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $content);
			$response = fwrite($sock, $content."\r\n");
		} catch(Exception $e) {
			return false;
		}
		return true;
	}
}
?>