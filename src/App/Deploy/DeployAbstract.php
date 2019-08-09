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
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use ZipArchive;

abstract class DeployAbstract implements DeployInterface
{
	const ARCHIVE_NAME = 'lamp-io.zip';

	const SQL_DUMP_NAME = 'dump.sql';

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

	protected $releaseFolder;

	/**
	 * DeployAbstract constructor.
	 * @param string $appPath
	 * @param Application $application
	 * @param int $releaseId
	 */
	public function __construct(string $appPath, Application $application, int $releaseId)
	{
		$this->appPath = $appPath;
		$this->application = $application;
		$this->consoleOutput = new ConsoleOutput();
		$this->releaseFolder = 'releases/release_' . $releaseId . '/';
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	protected function getZipApp()
	{
		$zipName = $this->appPath . self::ARCHIVE_NAME;
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
	 * @param string $command
	 * @param string $progressMessage
	 * @throws Exception
	 */
	protected function appRunCommand(string $appId, string $command, string $progressMessage)
	{
		$appRunsNewCommand = $this->application->find(AppRunsNewCommand::getDefaultName());
		$args = [
			'command' => AppRunsNewCommand::getDefaultName(),
			'app_id'  => $appId,
			'exec'    => $command,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		$appRunsNewCommand->run(new ArrayInput($args), $bufferOutput);
		/** @var Document $document */
		$document = Parser::parseResponseString($bufferOutput->fetch());
		$appRunId = $document->get('data.id');
		$progressBar = Command::getProgressBar($progressMessage, $this->consoleOutput);
		$progressBar->start();
		try {
			while (!AppRunsDescribeCommand::isExecutionCompleted($appRunId, $this->application)) {
				$progressBar->advance();
			}
		} catch (Exception $exception) {
			$progressBar->finish();
			$this->consoleOutput->writeln(PHP_EOL . '<error>Command ' . $command . ' was failed, output: ' . trim($exception->getMessage()) . '</error>');
			$question = new ConfirmationQuestion('Do you want to re-run command? (y/N)', false);
			$helper = new QuestionHelper();
			if ($helper->ask(new ArgvInput(), $this->consoleOutput, $question)) {
				$this->appRunCommand($appId, $command, $progressMessage);
			} else {
				exit(1);
			}
		}
		$progressBar->finish();
		$this->consoleOutput->write(PHP_EOL);
	}

	/**
	 * @param string $appId
	 * @throws Exception
	 */
	protected function clearApp(string $appId)
	{
		$command = 'rm -rf *';
		$this->appRunCommand($appId, $command, 'Removing default files');
	}

	/**
	 * @param string $appId
	 * @param string $localFile
	 * @param string $remotePath
	 * @throws Exception
	 */
	protected function uploadToApp(string $appId, string $localFile, string $remotePath)
	{
		$appRunsDescribeCommand = $this->application->find(FilesUploadCommand::getDefaultName());
		$args = [
			'command'     => FilesUploadCommand::getDefaultName(),
			'file'        => $localFile,
			'app_id'      => $appId,
			'remote_path' => $remotePath,
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
			'--yes'       => true,
		];
		$appRunsDescribeCommand->run(new ArrayInput($args), $this->consoleOutput);
	}


}