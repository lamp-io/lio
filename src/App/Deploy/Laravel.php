<?php

namespace Console\App\Deploy;

use Console\App\Commands\Files\FilesUpdateCommand;
use Symfony\Component\Console\Input\ArrayInput;

class Laravel extends DeployAbstract
{
	/**
	 * @param string $appId
	 * @param bool $isNewApp
	 * @throws \Exception
	 */
	public function deployApp(string $appId, bool $isNewApp)
	{
		$zip = $this->getZipApp();
		$this->clearApp($appId, $isNewApp);
		$this->uploadApp($appId, $zip);
		$this->unarchiveApp($appId, self::ARCHIVE_NAME);
		$this->setUpPermissions($appId);
		$this->deleteArchive($appId, self::ARCHIVE_NAME);

	}

	/**
	 * @param string $appPath
	 * @return bool
	 */
	public function isCorrectApp(string $appPath): bool
	{
		$composerJson = json_decode(file_get_contents($appPath . 'composer.json'), true);
		return array_key_exists('laravel/framework', $composerJson['require']);
	}

	/**
	 *
	 */
	public function deployDb()
	{

	}

	/**
	 * @param string $appId
	 * @throws \Exception
	 */
	protected function setUpPermissions(string $appId)
	{
		$directories = [
			'storage/logs',
			'storage/framework/sessions',
			'storage/framework/views',
		];
		foreach ($directories as $directory) {
			$appRunsDescribeCommand = $this->application->find(FilesUpdateCommand::getDefaultName());
			$args = [
				'command'     => FilesUpdateCommand::getDefaultName(),
				'app_id'      => $appId,
				'remote_path' => $directory,
				'--json'      => true,
			];
			$appRunsDescribeCommand->run(new ArrayInput($args), $this->consoleOutput);
		}
	}

}