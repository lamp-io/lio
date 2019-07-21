<?php

namespace Console\App\Deploy;

interface DeployInterface
{
	public function deployApp(string $appId, bool $isNewApp);

	public function isCorrectApp(string $appPath): bool;

	public function deployDb();
}