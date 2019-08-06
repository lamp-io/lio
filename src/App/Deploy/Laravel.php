<?php

namespace Console\App\Deploy;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\AppRuns\AppRunsDescribeCommand;
use Console\App\Commands\AppRuns\AppRunsNewCommand;
use Console\App\Commands\Command;
use Console\App\Commands\Files\FilesUpdateCommand;
use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class Laravel extends DeployAbstract
{
	/**
	 * @param string $appId
	 * @param bool $isNewApp
	 * @throws Exception
	 */
	public function deployApp(string $appId, bool $isNewApp)
	{
		$zip = $this->getZipApp();
		if ($isNewApp) {
			$this->clearApp($appId);
		}
		$this->uploadApp($appId, $zip);
		$this->unarchiveApp($appId, $this->releaseFolder . self::ARCHIVE_NAME);
		$this->setUpPermissions($appId);
		$this->deleteArchive($appId, $this->releaseFolder . self::ARCHIVE_NAME);
		$this->createSymlink($appId, $isNewApp);
		unlink($this->appPath . self::ARCHIVE_NAME);
	}

	/**
	 * @param string $appId
	 * @param bool $isNewApp
	 * @throws Exception
	 */
	private function createSymlink(string $appId, bool $isNewApp)
	{
		$appRunsNewCommand = $this->application->find(AppRunsNewCommand::getDefaultName());
		$symLinkOptions = ($isNewApp) ? '-s' : '-sfn';
		$args = [
			'command' => AppRunsNewCommand::getDefaultName(),
			'app_id'  => $appId,
			'exec'    => 'ln ' . $symLinkOptions .' ' . $this->releaseFolder . 'public public',
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		$appRunsNewCommand->run(new ArrayInput($args), $bufferOutput);
		/** @var Document $document */
		$document = Parser::parseResponseString($bufferOutput->fetch());
		$appRunId = $document->get('data.id');
		$progressBarMessage = 'Linking your current release';
		$progressBar = Command::getProgressBar($progressBarMessage, $this->consoleOutput);
		$progressBar->start();
		while (!AppRunsDescribeCommand::isExecutionCompleted($appRunId, $this->application)) {
			$progressBar->advance();
		}
		$progressBar->finish();
		$this->consoleOutput->write(PHP_EOL);
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
	 * @param string $appId
	 * @throws Exception
	 */
	private function setUpPermissions(string $appId)
	{
		$directories = [
			$this->releaseFolder . 'storage/logs',
			$this->releaseFolder . 'storage/framework/sessions',
			$this->releaseFolder . 'storage/framework/views',
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