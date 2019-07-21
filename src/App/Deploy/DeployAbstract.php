<?php

namespace Console\App\Deploy;

use Console\App\Commands\AppRuns\AppRunsDescribeCommand;
use Console\App\Commands\AppRuns\AppRunsNewCommand;
use Console\App\Commands\Command;
use Console\App\Commands\Files\FilesDeleteCommand;
use Console\App\Commands\Files\FilesUploadCommand;
use Console\App\Commands\Files\SubCommands\FilesUpdateUnarchiveCommand;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use ZipArchive;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class DeployAbstract implements DeployInterface
{
	const ARCHIVE_NAME = 'lamp-io.zip';

	/**
	 * @var string
	 */
	protected $appPath;

	/**
	 * @var Application
	 */
	protected $application;

	/**
	 * @var ConsoleOutput
	 */
	protected $consoleOutput;

	/**
	 * DeployAbstract constructor.
	 * @param string $appPath
	 * @param Application $application
	 */
	public function __construct(string $appPath, Application $application)
	{
		$this->appPath = $appPath;
		$this->application = $application;
		$this->consoleOutput = new ConsoleOutput();
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	protected function getZipApp()
	{
		$zipName = $this->appPath . DIRECTORY_SEPARATOR . self::ARCHIVE_NAME;
		if (file_exists($zipName)) {
			unlink($zipName);
		}
		$zip = new ZipArchive();
		$finder = new Finder();
		$progressBar = Command::getProgressBar('Creating a zip', $this->consoleOutput);
		$finder->in($this->appPath)->ignoreDotFiles(false);
		if (!$finder->hasResults()) {
			throw new Exception('Empty app directory');
		}

		if (!$zip->open($zipName, ZipArchive::CREATE)) {
			throw new Exception('Cant zip your app');
		}
		$progressBar->start();
		foreach ($finder as $file) {
			if (is_dir($file->getRealPath())) {
				$zip->addEmptyDir($file->getRelativePathname());
			} else {
				$zip->addFile($file->getRealPath(), $file->getRelativePathname());
			}
			$progressBar->advance();
		}

		$zip->close();
		$progressBar->finish();
		$this->consoleOutput->write(PHP_EOL);

		return $zipName;
	}

	/**
	 * @param string $appId
	 * @param bool $isNewApp
	 * @throws Exception
	 */
	protected function clearApp(string $appId, bool $isNewApp)
	{
		if (!$isNewApp) {
			/** @var QuestionHelper $questionHelper */
			$questionHelper = $this->application->getHelperSet()->get('question');
			$question = new ConfirmationQuestion('<info>All app files should be removed before deployment. Do you confirm it? (Y/n)</info>');
			if (!$questionHelper->ask(new ArgvInput(), $this->consoleOutput, $question)) {
				$this->consoleOutput->writeln('<info>Cant execute deploy command on a non empty app file system </info>');
				exit();
			}
		}
		$appRunsNewCommand = $this->application->find(AppRunsNewCommand::getDefaultName());
		$args = [
			'command' => AppRunsNewCommand::getDefaultName(),
			'app_id'  => $appId,
			'exec'    => 'rm -rf *',
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		$appRunsNewCommand->run(new ArrayInput($args), $bufferOutput);
		/** @var Document $document */
		$document = Parser::parseResponseString($bufferOutput->fetch());
		$appRunId = $document->get('data.id');
		$progressBarMessage = $isNewApp ? 'Removing default files' : 'Removing files';
		$progressBar = Command::getProgressBar($progressBarMessage, $this->consoleOutput);
		$progressBar->start();
		while (!$this->isAppCleared($appRunId)) {
			$progressBar->advance();
		}
		$progressBar->finish();
		$this->consoleOutput->write(PHP_EOL);
	}

	/**
	 * @param string $appRunId
	 * @return bool
	 * @throws Exception
	 */
	protected function isAppCleared(string $appRunId): bool
	{
		$appRunsDescribeCommand = $this->application->find(AppRunsDescribeCommand::getDefaultName());
		$args = [
			'command'    => AppRunsDescribeCommand::getDefaultName(),
			'app_run_id' => $appRunId,
			'--json'     => true,
		];
		$bufferOutput = new BufferedOutput();
		$appRunsDescribeCommand->run(new ArrayInput($args), $bufferOutput);
		/** @var Document $document */
		$document = Parser::parseResponseString($bufferOutput->fetch());
		return $document->get('data.attributes.complete');

	}

	/**
	 * @param string $appId
	 * @param string $zipPath
	 * @throws Exception
	 */
	protected function uploadApp(string $appId, string $zipPath)
	{
		$appRunsDescribeCommand = $this->application->find(FilesUploadCommand::getDefaultName());
		$args = [
			'command'     => FilesUploadCommand::getDefaultName(),
			'file'        => $zipPath,
			'app_id'      => $appId,
			'remote_path' => '/',
			'--json'      => true,
		];
		$appRunsDescribeCommand->run(new ArrayInput($args), $this->consoleOutput);
		$this->consoleOutput->write(PHP_EOL);
	}

	/**
	 * @param string $appId
	 * @param string $remotePath
	 * @throws Exception
	 */
	protected function unarchiveApp(string $appId, string $remotePath)
	{
		$appRunsDescribeCommand = $this->application->find(FilesUpdateUnarchiveCommand::getDefaultName());
		$args = [
			'command'     => FilesUpdateUnarchiveCommand::getDefaultName(),
			'remote_path' => $remotePath,
			'app_id'      => $appId,
			'--json'      => true,
		];
		$appRunsDescribeCommand->run(new ArrayInput($args), $this->consoleOutput);
		$this->consoleOutput->write(PHP_EOL);
	}

	/**
	 * @param string $appId
	 * @param string $remotePath
	 * @throws Exception
	 */
	protected function deleteArchive(string $appId, string $remotePath)
	{
		$appRunsDescribeCommand = $this->application->find(FilesDeleteCommand::getDefaultName());
		$args = [
			'command'     => FilesDeleteCommand::getDefaultName(),
			'remote_path' => $remotePath,
			'app_id'      => $appId,
			'--json'      => true,
		];
		$appRunsDescribeCommand->run(new ArrayInput($args), $this->consoleOutput);
		$this->consoleOutput->write(PHP_EOL);
	}


}