<?php
	require_once 'vendor/autoload.php';

	$helper = new \FileIconGenerator\Helper();
	$directory = 'icons';

	$fileSystem = new \Symfony\Component\Filesystem\Filesystem();
	$fileSystem->mkdir($directory);

	$builder = new \FileIconGenerator\Builder();

	$helper->create32x32($builder->getExtensions(), $directory);
