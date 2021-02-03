<?php

class Actions {

	const MINIMUM_ACCOUNT_LENGTH = 5; # defines the minimum length of an account number when updating/adding to the records
	const MINIMUM_USERNAME_LENGTH = 5; # defines the minimum length of a username when registering an account
	const MINIMUM_PASSWORD_LENGTH = 6; # defines the minimum length of the password when registering an account

	public static function load() {

		$settings = [];
		if (!file_exists(__DIR__.'/../daic.conf')) return false;
		$contents = file_get_contents(__DIR__.'/../daic.conf');

		foreach (explode("\n", $contents) as $line) {
			$line = trim($line);
			if ($line == '' || substr($line, 0, 1) == '#') continue;
			if (strpos($line, '=') === false) continue;

			$key = trim(substr($line, 0, strpos($line, '=')));
			$settings[$key] = trim(substr($line, strpos($line, '=') + 1));
		}

		return $settings;
	}

	public static function authenticate($mongo, $credentials) {

		$credentials = trim($credentials);
		if (strpos($credentials, ':') === false) return false;

		list($user, $pass) = explode(':', $credentials);
		$user = trim($user);
		$pass = trim($pass);
		$pass = md5($pass.md5($pass));

		$collection = $mongo->getCollection('users');
		$data = $collection->findOne(['user' => strtolower($user), 'password' => $pass, 'enabled' => true], ['projection' => ['_id' => 1], 'typeMap' => $mongo->typeMap]);
		if ($data == null) return false;
		else return ['id' => $data['_id'], 'user' => $user];
	}

	public static function query($mongo, $query, $user, $log, $errorLog) {

		# Set log message prefix

		if ($user == false) $logPrefix = 'guest > ';
		else $logPrefix = 'user '.$user['user'].' > ';

		# Process query

		$query = trim($query);
		if (strpos($query, ':') === false) return '500 Invalid format';
		list($domain, $account) = explode(':', $query);
		$domain = trim($domain);
		$account = trim($account);
		if (strpos($domain, '.') === false) return '501 Invalid domain';
		if (strlen($account) < self::MINIMUM_ACCOUNT_LENGTH) return '502 Invalid account';

		$collection = $mongo->getCollection('records');
		$data = $collection->findOne(['domain' => strtolower($domain)], ['projection' => ['account' => 1], 'typeMap' => $mongo->typeMap]);

		# Return 404 if no record was found on the server

		if ($data == null) {
			self::log($errorLog, $logPrefix.'missing record - '.strtolower($domain));
			return '404 Not found';
		}

		if (md5($account) == $data['account']) {
			self::log($log, $logPrefix.'successful query on '.strtolower($domain));
			return '200 Confirmed';
		}
		else {
			self::log($errorLog, $logPrefix.'invalid account number - '.strtolower($domain).' | '.$account);
			return '201 Unconfirmed';
		}

	}

	public static function update($mongo, $query, $user, $log) {

		$query = trim($query);
		if (strpos($query, ':') === false) return '500 Invalid format';
		list($domain, $account) = explode(':', $query);
		$domain = trim($domain);
		$account = trim($account);
		if (strpos($domain, '.') === false) return '501 Invalid domain';
		if (strlen($account) < self::MINIMUM_ACCOUNT_LENGTH) return '502 Invalid account';

		$collection = $mongo->getCollection('records');
		$data = $collection->findOne(['domain' => strtolower($domain)], ['projection' => ['_id' => 1, 'user' => 1], 'typeMap' => $mongo->typeMap]);
		if ($data == null) return '404 Not found';
		if ((string)$data['user'] != (string)$user['id']) return '403 Unauthorized';

		self::log($log, 'user '.$user['user'].' > updated domain '.$domain.' account information');
		$collection->updateOne(['domain' => strtolower($domain)], ['$set' => ['account' => md5($account)]]);
		return '200 Approved';
	}

	public static function register($mongo, $credentials, $emailsOnly = false, $log) {

		$credentials = trim($credentials);
		if (strpos($credentials, ':') === false) return ['response' => '500 Invalid format'];
		list($user, $pass) = explode(':', $credentials);
		$user = trim($user);
		$pass = trim($pass);

		if ($emailsOnly == 'true' && !preg_match('/^[\w\-\.]+@([\w\-]+\.)+[\w-]{2,4}$/', $user)) return ['response' => '501 Invalid username, only E-mails allowed'];
		if (strlen($user) < self::MINIMUM_USERNAME_LENGTH) return ['response' => '501 Invalid username'];
		if (strlen($pass) < self::MINIMUM_PASSWORD_LENGTH) return ['response' => '502 Invalid password'];
		$pass = md5($pass.md5($pass));

		$collection = $mongo->getCollection('users');
		$data = $collection->findOne(['user' => strtolower($user)], ['projection' => ['_id' => 1], 'typeMap' => $mongo->typeMap]);
		if ($data != null) return ['response' => '503 Account exists'];

		$id = new MongoDB\BSON\ObjectId();
		$data = $collection->insertOne(['_id' => $id, 'user' => $user, 'password' => $pass, 'enabled' => true]);

		self::log($log, 'user '.$user.' > registered');
		return ['id' => $id, 'user' => $user, 'response' => '200 Approved'];
	}

	public static function create($mongo, $query, $user, $log) {

		$query = trim($query);
		if (strpos($query, ':') === false) return '500 Invalid format';
		list($domain, $account) = explode(':', $query);
		$domain = trim($domain);
		$account = trim($account);
		if (strpos($domain, '.') === false) return '501 Invalid domain';
		if (strlen($account) < self::MINIMUM_ACCOUNT_LENGTH) return '502 Invalid account';

		$collection = $mongo->getCollection('records');
		$data = $collection->findOne(['domain' => strtolower($domain)], ['projection' => ['_id' => 1], 'typeMap' => $mongo->typeMap]);
		if ($data != null) return '503 Domain exists';

		self::log($log, 'user '.$user['user'].' > added new domain '.$domain);
		$collection->insertOne(['domain' => strtolower($domain), 'account' => md5($account), 'user' => $user['id']]);
		return '200 Approved';
	}

	private function log($log, $message) {

		$message = '['.date('Y-m-d H:i:s').']: '.$message."\n";
		echo $message;

		if ($log == '') return false;
		if (substr($log, 0, 2) == './') $log = __DIR__.'/../'.substr($log, 2);
		$fp = fopen($log, 'a');
		
		fwrite($fp, $message);
		fclose($fp);
	}
}

?>