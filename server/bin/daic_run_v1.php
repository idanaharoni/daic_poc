<?php

/**
 *
 * DAICS (Distributed Account Information Certification Server) Certification Server - v0.1
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Idan Aharoni <aharoni@gmail.com>
 * @version 0.1
 */

function writeMessage($sock, $message) {
	$message .= "\r\n";
	socket_write($sock, $message, strlen($message));
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

$result = socket_bind($socket, $settings['host'], $settings['port']);
if ($result == false) {
	die("Could not bind to socket, reason: ".socket_strerror(socket_last_error($socket))."\n");
}

# list for connections

echo "Setting up socket listener\n";
$result = socket_listen($socket, 100);

if ($result == false) {
	die("Could not set up socket listener, reason: ".socket_strerror(socket_last_error($socket))."\n");
}

echo "Listening to new clients...\n";

do {

	# accept incoming connections

	$msgSock  = socket_accept($socket);
	$userId = false;

	if ($msgSock == false) { 
		die("Could not accept incoming connection, reason: ".socket_strerror(socket_last_error($socket))."\n");
	}
	
	writeMessage($msgSock, '202 Accepted');
	$currentBuffer = '';

	do {

		if (($buffer = socket_read($msgSock, 1024000)) === false) {
			echo "Failed reading socket, reason: ".socket_strerror(socket_last_error($msgSock))."\n";
			break 2;
		}

		# If the character sent from the client is \r\n, it means the client finished sending a request and it should be processed

		if (substr($buffer, -1) == "\n") {

			if (strlen($buffer) > 1) $currentBuffer = trim($buffer);
			else $currentBuffer = trim($currentBuffer);

			# Handle HELO handshakes

			if (substr($currentBuffer, 0, 5) == 'HELO ') {
				writeMessage($msgSock, '200 Approved');
			}

			# Handle Authorization

			else if (substr($currentBuffer, 0, 5) == 'AUTH ') {
				$result = Actions::authenticate($mongo, substr($currentBuffer, 5));
				if ($result == false) writeMessage($msgSock, '401 Unauthorized');
				else {
					$userId = $result;
				}
			}

			# Handle query

			else if (substr($currentBuffer, 0, 6) == 'QUERY ') {

				if ($userId == false && strtolower($settings['enableGuestQueries']) != 'true') {
					writeMessage($msgSock, '401 Unauthorized');
				} else {
					$result = Actions::query($mongo, substr($currentBuffer, 6), $userId, $settings['accessLog'], $settings['errorLog']);
					if ($result == 200) writeMessage($msgSock, '200 Confirmed');
					else if ($result == 201) writeMessage($msgSock, '201 Unconfirmed');
					else if ($result == 404) writeMessage($msgSock, '404 Not found');
					else if ($result == 500) writeMessage($msgSock, '500 Invalid format');
					else if ($result == 501) writeMessage($msgSock, '501 Invalid domain');
					else if ($result == 502) writeMessage($msgSock, '502 Invalid account');
					else {
						writeMessage($msgSock, $result);
					}
				}
			}

			# Handle remote update

			else if (substr($currentBuffer, 0, 7) == 'UPDATE ') {
				if ($userId == false || strtolower($settings['enableRemoteUpdates']) != 'true') {
					writeMessage($msgSock, '401 Unauthorized');
				} else {
					$result = Actions::update($mongo, substr($currentBuffer, 7), $userId, $settings['accessLog']);
					if ($result == 200) writeMessage($msgSock, '200 Approved');
					else if ($result == 404) writeMessage($msgSock, '404 Not found');
					else if ($result == 500) writeMessage($msgSock, '500 Invalid format');
					else if ($result == 501) writeMessage($msgSock, '501 Invalid domain');
					else if ($result == 502) writeMessage($msgSock, '502 Invalid account');
					else {
						writeMessage($msgSock, $result);
					}
				}
			}

			# Handle registration

			else if (substr($currentBuffer, 0, 9) == 'REGISTER ') {
				if ($userId != false || strtolower($settings['enableRegistration']) != 'true') {
					writeMessage($msgSock, '401 Unauthorized');
				} else {
					$result = Actions::register($mongo, substr($currentBuffer, 9), $settings['accessLog']);
					if ($result['response'] == 200) writeMessage($msgSock, '200 Approved');
					else if ($result['response'] == 500) writeMessage($msgSock, '500 Invalid format');
					else if ($result['response'] == 501) writeMessage($msgSock, '501 Invalid username');
					else if ($result['response'] == 502) writeMessage($msgSock, '502 Invalid password');
					else if ($result['response'] == 503) writeMessage($msgSock, '503 Account exists');
					else {
						writeMessage($msgSock, $result);
					}
					writeMessage($msgSock, $result['response']);
					$userId = $result['id'];
				}
			}

			# Handle record creation

			else if (substr($currentBuffer, 0, 7) == 'CREATE ') {
				if ($userId == false || strtolower($settings['enableAdditions']) != 'true') {
					writeMessage($msgSock, '401 Unauthorized');
				} else {
					$result = Actions::create($mongo, substr($currentBuffer, 7), $userId, $settings['accessLog']);
					if ($result == 200) writeMessage($msgSock, '200 Approved');
					else if ($result == 500) writeMessage($msgSock, '500 Invalid format');
					else if ($result == 501) writeMessage($msgSock, '501 Invalid domain');
					else if ($result == 502) writeMessage($msgSock, '502 Invalid account');
					else if ($result == 503) writeMessage($msgSock, '503 Domain exists');
					else {
						writeMessage($msgSock, $result);
					}
				}
			}

			else if (substr($currentBuffer, 0, 4) == 'QUIT') {
				break;
			}

			else {
				writeMessage($msgSock, '500 Unknown command');
			}
		} 

		# If the character sent from the client is other than \r\n, add the new character to the request

		else {
			$currentBuffer .= $buffer;
		}
	} while (true);

	socket_close($msgSock);

} while (true);

socket_close($socket);






?>