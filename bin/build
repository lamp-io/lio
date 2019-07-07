#!/usr/bin/env php
<?php

if (file_exists(dirname(__DIR__, 3) . '/autoload.php')) {
	require_once dirname(__DIR__, 3) . '/autoload.php';
} else {
	require_once dirname(__DIR__) . '/vendor/autoload.php';
}

use Console\App\Helpers\Compiler;

$compiler = new Compiler();
$compiler->compile();