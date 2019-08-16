<?php

namespace Console\App\Helpers;

use Console\App\Commands\Files\FilesListCommand;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DeployHelper
{
	const RELEASE_FOLDER = 'releases';

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

	/**
	 * @param string $appId
	 * @param Application $application
	 * @return bool
	 * @throws Exception
	 */
	static public function isReleasesFolderExists(string $appId, Application $application): bool
	{
		$filesListCommand = $application->find(FilesListCommand::getDefaultName());
		$args = [
			'command' => FilesListCommand::getDefaultName(),
			'app_id'  => $appId,
			'file_id' => self::RELEASE_FOLDER,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		return $filesListCommand->run(new ArrayInput($args), $bufferOutput) == '0';
	}
}