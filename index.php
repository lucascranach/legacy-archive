<?php

require_once 'src/classes/Config.class.php';
$config = new Config;
$host = $config->getSection('host');

header('Location: ' . $config->getBaseUrl() .'/gallery');
exit();
