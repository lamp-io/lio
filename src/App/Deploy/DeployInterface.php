<?php

namespace Console\App\Deploy;

interface DeployInterface
{
	public function deployApp(string $appId);

	public function isCorrectApp(): bool;

	public function deployDb();
}