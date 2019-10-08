<?php

namespace Lio\App\Deployers;

use Closure;
use Lio\App\Commands\AppRuns\AppRunsDescribeCommand;
use Lio\App\Commands\AppRuns\AppRunsNewCommand;
use Lio\App\Commands\Apps\AppsDescribeCommand;
use Lio\App\Commands\Databases\DatabasesDescribeCommand;
use Lio\App\Commands\DbBackups\DbBackupsDescribeCommand;
use Lio\App\Commands\DbBackups\DbBackupsNewCommand;
use Lio\App\Commands\DbRestores\DbRestoresDescribeCommand;
use Lio\App\Commands\DbRestores\DbRestoresNewCommand;
use Lio\App\Commands\Files\FilesDeleteCommand;
use Lio\App\Commands\Files\FilesUpdateCommand;
use Lio\App\Commands\Files\FilesUploadCommandWrapper;
use Lio\App\Commands\Files\SubCommands\FilesUpdateMoveCommand;
use Lio\App\Commands\Files\SubCommands\FilesUpdateUnarchiveCommand;
use Lio\App\Helpers\AuthHelper;
use Lio\App\Helpers\CommandsHelper;
use Lio\App\Helpers\DeployHelper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use ZipArchive;
use GuzzleHttp\Exception\GuzzleException;

abstract class DeployerAbstract implements DeployInterface
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

	/**
	 * @var string
	 */
	protected $releaseFolder;

	protected $config;

	protected $isFirstDeploy;

	protected $steps = [];

	protected $isNewDbInstance;

	/**
	 * @var Client
	 */
	protected $httpClient;

	/**
	 * DeployAbstract constructor.
	 * @param Application $application
	 * @param array $config
	 * @param Client $httpClient
	 */
	public function __construct(Application $application, array $config, Client $httpClient)
	{
		$this->application = $application;
		$this->consoleOutput = new ConsoleOutput();
		$this->config = $config;
		$this->httpClient = $httpClient;
	}

	/**
	 * @param string $appPath
	 * @param bool $isFirstDeploy
	 * @param bool $isNewDbInstance
	 * @throws Exception
	 * @throws GuzzleException
	 */
	public function deployApp(string $appPath, bool $isFirstDeploy, bool $isNewDbInstance)
	{
		$this->appPath = $appPath;
		$this->isFirstDeploy = $isFirstDeploy;
		$this->isNewDbInstance = $isNewDbInstance;
		$this->releaseFolder = DeployHelper::RELEASE_FOLDER . '/' . $this->config['release'] . '/';
		if ($this->isFirstDeploy) {
			$this->initApp($this->config['app']['id']);
		}
		if ($this->isFirstDeploy) {
			$this->clearApp();
		}
		if (!file_exists($appPath . DIRECTORY_SEPARATOR . '.env')) {
			file_put_contents($appPath . DIRECTORY_SEPARATOR . '.env', '');
		}
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	protected function getArtifact()
	{
		$stepName = 'zip';
		$this->setStep($stepName, function () {
			if (file_exists($this->appPath . self::ARCHIVE_NAME)) {
				unlink($this->appPath . self::ARCHIVE_NAME);
			}
		});
		$zipName = $this->appPath . self::ARCHIVE_NAME;
		if (file_exists($zipName)) {
			unlink($zipName);
		}
		$zip = new ZipArchive();
		$finder = new Finder();
		$progressBar = CommandsHelper::getProgressBar('Creating an artifact', $this->consoleOutput);
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
		$this->updateStepToSuccess($stepName);
		return $zipName;
	}

	/**
	 * @return string
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function backupDatabase(): string
	{
		if ($this->config['database']['type'] == 'external' || $this->config['database']['system'] == 'sqlite') {
			return '';
		}
		$step = 'backupDatabase';
		$this->setStep($step, function () {
			return;
		});
		$dbBackupNewCommand = $this->application->find(DbBackupsNewCommand::getDefaultName());
		$args = [
			'command'     => DbBackupsNewCommand::getDefaultName(),
			'database_id' => $this->config['database']['id'],
			'--json'      => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($dbBackupNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			$dbBackupId = $document->get('data.id');
			$progressBar = CommandsHelper::getProgressBar('Backup current database state', $this->consoleOutput);
			$progressBar->start();
			while (!DbBackupsDescribeCommand::isDbBackupCreated($dbBackupId, $this->application)) {
				$progressBar->advance();
			}
			$progressBar->finish();
			$this->consoleOutput->write(PHP_EOL);
			return $dbBackupId;
		} else {
			throw new Exception('Database backup creation failed');
		}
	}

	/**
	 * @param string $dbBackupId
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function restoreDatabase(string $dbBackupId)
	{
		$dbRestoreNewCommand = $this->application->find(DbRestoresNewCommand::getDefaultName());
		$args = [
			'command'      => DbRestoresNewCommand::getDefaultName(),
			'database_id'  => $this->config['database']['id'],
			'db_backup_id' => $dbBackupId,
			'--json'       => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($dbRestoreNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			$document = Parser::parseResponseString($bufferOutput->fetch());
			/** @var Document $document */
			$dbRestoreId = $document->get('data.id');
			$progressBar = CommandsHelper::getProgressBar('Restoring db to previous state', $this->consoleOutput);
			$progressBar->start();
			while (!DbRestoresDescribeCommand::isDbRestoreCompleted($dbRestoreId, $this->application)) {
				$progressBar->advance();
			}
			$progressBar->finish();
			$this->consoleOutput->write(PHP_EOL);
		} else {
			throw new Exception('Database restore failed');
		}
	}

	/**
	 * @param string $appId
	 * @param string $command
	 * @param string $progressMessage
	 * @throws GuzzleException
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
		if ($appRunsNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			$appRunId = $document->get('data.id');
			$progressBar = CommandsHelper::getProgressBar($progressMessage, $this->consoleOutput);
			$progressBar->start();
			while (!AppRunsDescribeCommand::isExecutionCompleted($appRunId, $this->application)) {
				$progressBar->advance();
			}
			$progressBar->finish();
			$this->consoleOutput->write(PHP_EOL);
		} else {
			throw new Exception('CommandWrapper ' . $command . '. Failed');
		}
	}

	/**
	 * @param string $appId
	 * @throws Exception
	 */
	protected function initApp(string $appId)
	{
		$progressBar = CommandsHelper::getProgressBar('Initializing app', $this->consoleOutput);
		$progressBar->start();
		while (!$this->isAppRunning($appId)) {
			$progressBar->advance();
		}
		$this->consoleOutput->write(PHP_EOL);
	}

	/**
	 * @param string $appId
	 * @return bool
	 * @throws Exception
	 */
	protected function isAppRunning(string $appId): bool
	{
		$appsDescribeCommand = $this->application->find(AppsDescribeCommand::getDefaultName());
		$args = [
			'command' => AppsDescribeCommand::getDefaultName(),
			'app_id'  => $appId,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($appsDescribeCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			return $document->get('data.attributes.status') === 'running';
		} else {
			throw new Exception($bufferOutput->fetch());
		}
	}

	/**
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function clearApp()
	{
		$step = 'clearApp';
		$this->setStep($step, function () {
			return;
		});
		try {
			$this->deleteFile('public', 'Removing default files');
		} catch (ClientException $clientException) {
			$this->consoleOutput->write(PHP_EOL);
		}
	}

	/**
	 * @param array $updates
	 * @throws GuzzleException
	 */
	protected function updateEnvFile(array $updates)
	{
		$step = 'updateEnvFile';
		$this->setStep($step, function () {
			return;
		});
		if (!empty($updates)) {
			$currentEnv = file_get_contents($this->appPath . '.env');
			foreach ($updates as $key => $item) {
				if (preg_match('/' . $key . '=(.*?)\\n/', $currentEnv)) {
					$currentEnv = preg_replace('/' . $key . '=(.*?)\\n/', $key . '=' . $item . PHP_EOL, $currentEnv);
				} else {
					$currentEnv .= $key . '=' . $item . PHP_EOL;
				}
			}
			$this->updateFile('shared/.env', $this->config['app']['id'], $currentEnv);
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param string $fileId
	 * @param bool $recur
	 * @param string $message
	 * @return Response
	 * @throws GuzzleException
	 */
	protected function giveFileApachePermission(string $fileId, string $message, bool $recur): Response
	{
		return $this->sendRequest(
			sprintf(
				FilesUpdateCommand::API_ENDPOINT,
				$this->config['app']['id'],
				$fileId,
				($recur) ? '?recur=true' : ''
			),
			'PATCH',
			$message,
			json_encode([
				'data' => [
					'attributes' => [
						'apache_writable' => true,
					],
					'id'         => $fileId,
					'type'       => 'files',
				],
			])
		);
	}

	/**
	 * @param string $fileId
	 * @param string $appId
	 * @param string $content
	 * @return Response
	 * @throws GuzzleException
	 */
	protected function updateFile(string $fileId, string $appId, string $content): Response
	{
		return $this->sendRequest(
			sprintf(
				FilesUpdateCommand::API_ENDPOINT,
				$appId,
				$fileId,
				''
			),
			'PATCH',
			'Updating ' . $fileId,
			json_encode([
				'data' => [
					'attributes' => [
						'contents' => $content,
					],
					'id'         => $fileId,
					'type'       => 'files',
				],
			]));
	}

	/**
	 * @param string $dbHost
	 * @param string $dbName
	 * @param string $dbUser
	 * @param string $dbPassword
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function importSqlDump(string $dbHost, string $dbName, string $dbUser, string $dbPassword)
	{
		$step = 'ImportSqlDump';
		$this->setStep($step, function () {
			return;
		});
		if ($this->uploadFile($this->config['database']['sql_dump'], $this->releaseFolder . self::SQL_DUMP_NAME) == '0') {
			$command = sprintf(
				'mysql -u %s --host=%s --password=%s  %s < %s',
				$dbUser,
				$dbHost,
				$dbPassword,
				$dbName,
				$this->releaseFolder . self::SQL_DUMP_NAME
			);
			$this->appRunCommand(
				$this->config['app']['id'],
				$command,
				'Importing sql dump'
			);
			$this->updateStepToSuccess($step);
		} else {
			throw new Exception('Uploading sql dump to app failed');
		}
	}

	/**
	 * @param string $dbId
	 * @return bool
	 * @throws Exception
	 */
	protected function isDbAlreadyRunning(string $dbId): bool
	{
		$appRunsNewCommand = $this->application->find(DatabasesDescribeCommand::getDefaultName());
		$args = [
			'command'     => DatabasesDescribeCommand::getDefaultName(),
			'database_id' => $dbId,
			'--json'      => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($appRunsNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			return $document->get('data.attributes.status') === 'running';
		} else {
			throw new Exception('Checking db status failed');
		}
	}

	/**
	 * @param string $dbHost
	 * @throws Exception
	 */
	protected function initDatabase(string $dbHost)
	{
		$progressBar = CommandsHelper::getProgressBar('Initializing database', $this->consoleOutput);
		$progressBar->start();
		$dbIsRunning = false;
		while (!$dbIsRunning) {
			$dbIsRunning = $this->isDbAlreadyRunning($dbHost);
			$progressBar->advance();
		}
		$progressBar->finish();
		$this->consoleOutput->write(PHP_EOL);
	}

	/**
	 * @param string $dbHost
	 * @param string $dbName
	 * @param string $dbUser
	 * @param string $dbPassword
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function createDatabase(string $dbHost, string $dbName, string $dbUser, string $dbPassword)
	{
		$step = 'CreateDatabase';
		$this->setStep($step, function () use ($dbName, $dbUser, $dbPassword, $dbHost) {
			$command = sprintf(
				'mysql --user=%s --host=%s --password=%s --execute "drop database %s;"',
				$dbUser,
				$dbHost,
				$dbPassword,
				$dbName
			);
			$this->appRunCommand(
				$this->config['app']['id'],
				$command,
				'Drop database'
			);
		});

		$command = sprintf(
			'mysql --user=%s --host=%s --password=%s --execute "create database %s;"',
			$dbUser,
			$dbHost,
			$dbPassword,
			$dbName
		);
		$this->appRunCommand(
			$this->config['app']['id'],
			$command,
			'Creating database `' . $dbName . '`'
		);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function shareEnvFile()
	{
		$step = 'shareEnvFile';
		$this->setStep($step, function () {
			return;
		});
		if ($this->isFirstDeploy) {
			$this->moveFile($this->releaseFolder . '.env', 'shared/.env');
		} else {
			$this->deleteFile($this->releaseFolder . '.env', 'Deleting .env file from release');
		}

		$this->appRunCommand(
			$this->config['app']['id'],
			'ln -s /var/www/shared/.env ' . $this->releaseFolder . '.env',
			'Symlink shared .env to release'
		);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param string $fileId
	 * @param string $message
	 * @return  Response
	 * @throws GuzzleException
	 */
	protected function deleteFile(string $fileId, string $message): Response
	{
		return $this->sendRequest(
			sprintf(
				FilesDeleteCommand::API_ENDPOINT,
				$this->config['app']['id'],
				$fileId
			),
			'DELETE',
			$message
		);
	}

	/**
	 * @param string $localFile
	 * @param string $fileId
	 * @return int
	 * @throws Exception
	 */
	protected function uploadFile(string $localFile, string $fileId): int
	{
		$filesUploadCommand = $this->application->find(FilesUploadCommandWrapper::getDefaultName());
		$args = [
			'command' => FilesUploadCommandWrapper::getDefaultName(),
			'file'    => $localFile,
			'app_id'  => $this->config['app']['id'],
			'file_id' => $fileId,
			'--json'  => true,
		];

		return $filesUploadCommand->run(new ArrayInput($args), $this->consoleOutput);
	}

	/**
	 * @param string $fileId
	 * @param string $target
	 * @return Response
	 * @throws GuzzleException
	 */
	protected function moveFile(string $fileId, string $target): Response
	{
		return $this->sendRequest(
			sprintf(
				FilesUpdateMoveCommand::API_ENDPOINT,
				$this->config['app']['id'],
				$fileId,
				'command=move'
			),
			'PATCH',
			'Moving ' . $fileId . ' to ' . $target,
			json_encode([
				'data' => [
					'attributes' => [
						'target' => $target,
					],
					'id'         => $fileId,
					'type'       => 'files',
				],
			]));
	}

	/**
	 * @param string $localFile
	 * @param string $fileId
	 * @throws Exception
	 */
	protected function uploadArtifact(string $localFile, string $fileId)
	{
		$step = 'uploadToApp';
		$this->setStep($step, function () {
			$fileId = $this->isFirstDeploy ? DeployHelper::RELEASE_FOLDER : $this->releaseFolder;
			$this->deleteFile($fileId, 'Clean up failed deploy');
		});
		if ($this->uploadFile($localFile, $fileId) == '0') {
			$this->consoleOutput->write(PHP_EOL);
			$this->updateStepToSuccess($step);
		} else {
			throw new Exception('Uploading app failed');
		}
	}

	/**
	 * @param string $fileId
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function unarchiveApp(string $fileId)
	{
		$step = 'unarchiveApp';
		$this->setStep($step, function () {
			return;
		});

		$this->sendRequest(
			sprintf(
				FilesUpdateUnarchiveCommand::API_ENDPOINT,
				$this->config['app']['id'],
				$fileId,
				http_build_query(['command' => 'unarchive'])
			),
			'PATCH',
			'Extracting ' . $fileId,
			json_encode([
				'data' => [
					'id'   => $fileId,
					'type' => 'files',
				],
			])
		);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param string $fileId
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function deleteArtifact(string $fileId)
	{
		$step = 'deleteArchiveRemote';
		$this->setStep($step, function () {
			return;
		});
		$this->deleteFile($fileId, 'Deleting an artifact');
		if (file_exists($this->appPath . self::ARCHIVE_NAME)) {
			unlink($this->appPath . self::ARCHIVE_NAME);
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param string $url
	 * @param string $httpType
	 * @param string $progressBarMessage
	 * @param string $body
	 * @param array $headers
	 * @return Response
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function sendRequest(string $url, string $httpType, string $progressBarMessage = '', string $body = '', array $headers = []): Response
	{
		$output = !empty($progressBarMessage) ? new ConsoleOutput() : new NullOutput();
		if (empty($headers)) {
			$headers = [
				'Content-type'  => 'application/vnd.api+json',
				'Accept'        => 'application/vnd.api+json',
				'Authorization' => 'Bearer ' . AuthHelper::getToken(),
			];
		}
		$progressBar = CommandsHelper::getProgressBar($progressBarMessage, $output);
		$response = $this->httpClient->request(
			$httpType,
			$url,
			[
				'headers'  => $headers,
				'body'     => $body,
				'progress' => function () use ($progressBar) {
					$progressBar->advance();
				},
			]
		);
		$progressBar->finish();
		$output->write(PHP_EOL);
		return $response;
	}

	/**
	 * @param array $directories
	 * @param bool $recur
	 * @throws GuzzleException
	 */
	protected function setUpPermissions(array $directories = [], bool $recur = false)
	{
		$step = 'setDirectoryPermissions';
		$this->setStep($step, function () {
			return;
		});

		if (!empty($this->config['apache_permissions_dir']) && is_array($this->config['apache_permissions_dir'])) {
			$directories = array_merge($directories, $this->config['apache_permissions_dir']);
		}

		foreach ($directories as $directory) {
			try {
				$this->giveFileApachePermission(
					$this->releaseFolder . $directory,
					'Setting apache writable ' . $directory,
					$recur);
			} catch (ClientException $clientException) {
				$this->consoleOutput->writeln(PHP_EOL . '<comment>Directory ' . $directory . '/ not exists</comment>');
			}
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function initSqliteDatabase()
	{
		$step = 'initSqliteDatabase';
		$this->setStep($step, function () {
			return;
		});
		if (!empty($this->config['database']['sql_dump'])) {
			if ($this->uploadFile($this->config['database']['sql_dump'], DeployHelper::SQLITE_RELATIVE_REMOTE_PATH) != '0') {
				throw new Exception('Cant up load mysqli dump');
			}
			$this->consoleOutput->write(PHP_EOL);
		} else {
			$url = sprintf(
				FilesUploadCommandWrapper::API_ENDPOINT,
				$this->config['app']['id']
			);
			$this->sendRequest($url, 'POST', 'Creating sqlite database', json_encode([
				'data' => [
					'attributes' => [
						'apache_writable' => true,
					],
					'id'         => DeployHelper::SQLITE_RELATIVE_REMOTE_PATH,
					'type'       => 'files',
				],
			]));
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param array $skipCommands
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function runCommands(array $skipCommands = [])
	{
		$step = 'runCommands';
		$this->setStep($step, function () {
			return;
		});
		if (!empty($this->config['commands'])) {
			foreach ($this->config['commands'] as $command) {
				foreach ($skipCommands as $skipCommand) {
					if (preg_match('/' . $skipCommand . '/', $command)) {
						$skip = true;
					}
				}
				if (empty($skip)) {
					$this->appRunCommand(
						$this->config['app']['id'],
						'cd ' . $this->releaseFolder . ' && ' . $command,
						$command
					);
				}
				$skip = false;
			}
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param string $migrationCommand
	 * @param string $dbBackupId
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function runMigrations(string $migrationCommand, string $dbBackupId = '')
	{
		if (!empty($this->config['no_migrations'])) {
			return;
		}
		$step = 'runMigrations';
		$this->setStep($step, function () use ($dbBackupId) {
			if (!empty($dbBackupId)) {
				$this->restoreDatabase($dbBackupId);
			}
		});

		$this->appRunCommand(
			$this->config['app']['id'],
			'php ' . $this->releaseFolder . $migrationCommand,
			'Migrating schema'
		);
		$this->updateStepToSuccess($step);

	}

	/**
	 * @param string $fileId
	 * @param string $target
	 * @param bool $isFileExists
	 * @param string $message
	 * @return Response
	 * @throws GuzzleException
	 */
	protected function makeSymlink(string $fileId, string $target, bool $isFileExists, string $message): Response
	{
		if ($isFileExists) {
			$url = sprintf(
				FilesUpdateCommand::API_ENDPOINT,
				$this->config['app']['id'],
				'public',
				''
			);
			$httpType = 'PATCH';
		} else {
			$url = sprintf(
				FilesUploadCommandWrapper::API_ENDPOINT,
				$this->config['app']['id']
			);
			$httpType = 'POST';
		}

		return $this->sendRequest($url, $httpType, $message, json_encode([
			'data' => [
				'attributes' => [
					'target'     => $target,
					'is_dir'     => false,
					'is_symlink' => true,
				],
				'id'         => $fileId,
				'type'       => 'files',
			],
		]));
	}

	/**
	 * @param string $releasePublic
	 * @param string $message
	 * @param bool $isFirstDeploy
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function symlinkRelease(string $releasePublic, string $message, bool $isFirstDeploy = false)
	{
		$step = 'symlinkRelease';
		$this->setStep($step, function () {
			return;
		});

		$this->makeSymlink('public', $releasePublic, !$isFirstDeploy, $message);
		$this->updateStepToSuccess($step);
	}

	protected function createSharedStorage(array $commands)
	{
		$step = 'createSymlinkStorage';
		$this->setStep($step, function () {
			return;
		});

		foreach ($commands as $command) {
			$command['execute']($command['message']);
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param string $name
	 * @param string $message
	 * @param bool $isDir
	 * @throws GuzzleException
	 */
	protected function createFileIfNotExists(string $name, string $message, bool $isDir = false)
	{
		$postFileUrl = sprintf(
			FilesUploadCommandWrapper::API_ENDPOINT,
			$this->config['app']['id']
		);
		$postFileBody = json_encode([
			'data' => [
				'attributes' => [
					'is_dir' => $isDir,
				],
				'id'         => $name,
				'type'       => 'files',
			],
		]);
		try {
			$this->sendRequest($postFileUrl, 'POST', $message, $postFileBody);
		} catch (ClientException $clientException) {
			return;
		}

	}

	/**
	 * @param string $step
	 * @param Closure $revertFunction
	 */
	protected function setStep(string $step, Closure $revertFunction)
	{
		$this->steps[$step] = [
			'status'         => 'init',
			'revertFunction' => $revertFunction,
		];
	}

	/**
	 * @param string $step
	 */
	protected function updateStepToSuccess(string $step)
	{
		$this->steps[$step]['status'] = 'success';
	}

}