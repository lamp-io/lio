<?php

namespace Console\App\Helpers;

class DeployHelper
{
	/**
	 * @param string $appType
	 * @param string $appPath
	 * @return bool
	 */
	static public function isCorrectApp(string $appType, string $appPath): bool
	{
		switch ($appType) {
			case 'laravel':
				$composerJson = json_decode(file_get_contents($appPath . 'composer.json'), true);
				return array_key_exists('laravel/framework', $composerJson['require']);
			default:
				return false;
		}
	}
}