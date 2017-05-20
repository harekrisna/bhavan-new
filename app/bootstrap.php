<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

$configurator->setDebugMode(['2a00:1028:83a2:4e0a:c83:c413:7021:5f26',
							 '2a00:1028:83a2:4e0a:b840:19bf:92f0:c407',
							 '89.102.21.18']); // enable for your remote IP
$configurator->enableDebugger(__DIR__ . '/../log');


$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
