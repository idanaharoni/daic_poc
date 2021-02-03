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

# Load MongoDB instance

require_once __DIR__.'/lib/mongoConnect.php';
$mongo = DBConnection::instantiate();

# Load settings

require __DIR__.'/lib/settings.php';
$settings = Settings::load() or die("Unable to load configuration file\n");

# Load actions class

require __DIR__.'/lib/actions.php';

# Run server

require __DIR__.'/bin/daic_run.php';



?>