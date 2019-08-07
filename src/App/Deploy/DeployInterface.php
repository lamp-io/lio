<?php

namespace Console\App\Deploy;

interface DeployInterface
{
	/**
	 * @param string $appId
	 * @param bool $isNewApp
	 * @param bool $isNewDatabase
	 * @param array $config
	 * @return mixed
	 */
	public function deployApp(string $appId, bool $isNewApp, bool $isNewDatabase, array $config);

	/**
	 * @param string $appPath
	 * @return bool
	 */
	public function isCorrectApp(string $appPath): bool;
}