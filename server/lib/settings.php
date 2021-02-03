<?php

class Settings {

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
}

?>