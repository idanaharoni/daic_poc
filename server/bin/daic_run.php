<?php

/**
 *
 * DAIC (Distributed Account Information Certification) Server - v0.1
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Idan Aharoni <aharoni@gmail.com>
 * @version 0.1
 */

function writeMessage($sock, $message) {
	$message .= "\r\n";
	$result = @socket_write($sock, $message, strlen($message));

	if ($result == false) return false;
	return true;
}
# Disable timeout

set_time_limit(0);

# Create socket

echo "Creating socket\n";
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket == false) {
	 die("Could not create socket, reason: ".socket_strerror(socket_last_error())."\n");
}

# bind socket

echo "Binding socket\n";

$result = @socket_bind($socket, $settings['host'], $settings['port']);
if ($result == false) {
	die("Could not bind to socket, reason: ".socket_strerror(socket_last_error($socket))."\n");
}

$max_clients = 100;

# list for connections

echo "Setting up socket listener\n";
$result = socket_listen($socket, 100);

if ($result == false) {
	die("Could not set up socket listener, reason: ".socket_strerror(socket_last_error($socket))."\n");
}

echo "Listening to new clients...\n";

$client = [];
do {

	# Handle multiple clients

	$read[0] = $socket;
	for ($i = 0; $i < $settings['maxClients']; $i++) {
		if (isset($client[$i]['socket'])) {
			$read[$i+1] = $client[$i]['socket'];
		}
	}

	$write = NULL;
	$except = NULL;
	$to = 5;

	if (socket_select($read, $write, $except, $to) < 1) {
		continue;
	}

	if (in_array($socket, $read)) {
		for ($i = 0; $i < $settings['maxClients']; $i++) {
			if (empty($client[$i]['socket'])) {
				$client[$i]['socket'] = socket_accept($socket);
				$client[$i]['auth'] = false;
				echo "New client connected $i\r\n";
				writeMessage($client[$i]['socket'], '202 Accepted');
				break;
			}
			elseif ($i == $settings['maxClients'] - 1) {
				echo "Too many clients...\r\n";
			}
		}
	}

	# accept incoming connections

	for ($i = 0; $i < $settings['maxClients']; $i++) {
		if (isset($client[$i]['socket'])) {
			if (in_array($client[$i]['socket'], $read)) {
				$input = socket_read($client[$i]['socket'], 1024);
				$input = trim($input);

				if (substr($input, 0, 5) == 'HELO ') {
					$ws = writeMessage($client[$i]['socket'], '200 Approved');
				}

				# Handle Authorization

				else if (substr($input, 0, 5) == 'AUTH ') {
					$result = Actions::authenticate($mongo, substr($input, 5));
					if ($result == false) $ws = writeMessage($client[$i]['socket'], '401 Unauthorized');
					else {
						$ws = writeMessage($client[$i]['socket'], '200 Approved');
						$client[$i]['auth'] = $result;
					}
				}

				# Handle query

				else if (substr($input, 0, 6) == 'QUERY ') {

					if ($client[$i]['auth'] == false && strtolower($settings['enableGuestQueries']) != 'true') {
						$ws = writeMessage($client[$i]['socket'], '401 Unauthorized');
					} else {
						$result = Actions::query($mongo, substr($input, 6), $client[$i]['auth'], $settings['accessLog'], $settings['errorLog']);
						$ws = writeMessage($client[$i]['socket'], $result);
					}
				}

				# Handle remote update

				else if (substr($input, 0, 7) == 'UPDATE ') {
					if ($client[$i]['auth'] == false || strtolower($settings['enableRemoteUpdates']) != 'true') {
						$ws = writeMessage($client[$i]['socket'], '401 Unauthorized');
					} else {
						$result = Actions::update($mongo, substr($input, 7), $client[$i]['auth'], $settings['accessLog']);
						$ws = writeMessage($client[$i]['socket'], $result);
					}
				}

				# Handle registration

				else if (substr($input, 0, 9) == 'REGISTER ') {
					if ($client[$i]['auth'] != false || strtolower($settings['enableRegistration']) != 'true') {
						$ws = writeMessage($client[$i]['socket'], '401 Unauthorized');
					} else {
						$result = Actions::register($mongo, substr($input, 9), $settings['registerEmailsOnly'], $settings['accessLog']);
						$ws = writeMessage($client[$i]['socket'], $result['response']);
						if (isset($result['id'])) $client[$i]['auth'] = ['id' => $result['id'], 'user' => $result['user']];
					}
				}

				# Handle record creation

				else if (substr($input, 0, 7) == 'CREATE ') {
					if ($client[$i]['auth'] == false || strtolower($settings['enableAdditions']) != 'true') {
						$ws = writeMessage($client[$i]['socket'], '401 Unauthorized');
					} else {
						$result = Actions::create($mongo, substr($input, 7), $client[$i]['auth'], $settings['accessLog']);
						$ws = writeMessage($client[$i]['socket'], $result);
					}
				}

				else if (substr($input, 0, 4) == 'QUIT') {
					socket_close($client[$i]['socket']);
					unset($client[$i]);
					unset($read[$i+1]);
					echo "Client $i disconnected\n";
				}

				else {
					$ws = writeMessage($client[$i]['socket'], '500 Unknown command');
				}

				# If writing to socket fails, delete user connection

				if ($ws === false) {
					socket_close($client[$i]['socket']);
					unset($client[$i]);
					unset($read[$i+1]);
					echo "Client $i disconnected\n";
				}
			}
		}
	}

} while (true);

socket_close($socket);






?>