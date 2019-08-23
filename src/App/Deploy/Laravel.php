<?php

namespace Console\App\Deploy;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use Console\App\Commands\Databases\DatabasesDescribeCommand;
use Console\App\Commands\Files\FilesDeleteCommand;
use Console\App\Commands\Files\FilesUpdateCommand;
use Console\App\Commands\Files\FilesUploadCommand;
use Console\App\Helpers\DeployHelper;
use Dotenv\Dotenv;
use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use GuzzleHttp\Exception\GuzzleException;

class Laravel extends DeployAbstract
{
	/**
	 * @var array
	 */
	private $localEnv = [];

	/**
	 * @param string $appPath
	 * @param bool $isFirstDeploy
	 * @return void
	 * @throws Exception
	 * @throws GuzzleException
	 */
	public function deployApp(string $appPath, bool $isFirstDeploy)
	{
		parent::deployApp($appPath, $isFirstDeploy);
		(Dotenv::create($this->appPath))->load();
		$this->localEnv = $_ENV;
		$this->updateEnvFileToUpload();
		$zip = $this->getZipApp();
		if ($this->isFirstDeploy) {
			$this->clearApp();
		}
		$this->restoreLocalEnvFile();
		$this->uploadToApp($zip, $this->releaseFolder . self::ARCHIVE_NAME);
		$this->unarchiveApp($this->releaseFolder . self::ARCHIVE_NAME);
		$this->deleteArchiveRemote($this->releaseFolder . self::ARCHIVE_NAME);
		$this->createSymlinkStorage();
		$this->setUpPermissions();
		if ($this->isFirstDeploy) {
			$this->initDatabase();
			$this->createDatabaseUser();
			$this->createDatabase();
			if (!empty($this->config['database']['sql_dump'])) {
				$this->importSqlDump();
			}
			$dbBackupId = '';
		} else {
			$dbBackupId = $this->backupDatabase();
		}

		$this->runCommands();
		$this->artisanMigrate($dbBackupId);
		$this->symlinkRelease($this->releaseFolder, 'Linking your current release', $this->isFirstDeploy);
		$this->deleteArchiveLocal();
	}

	/**
	 * @param string $currentRelease
	 * @param string $previousRelease
	 * @throws Exception
	 * @throws GuzzleException
	 */
	public function revert(string $currentRelease, string $previousRelease)
	{
		$migrationsDif = $this->getMigrationsDif($currentRelease, $previousRelease);
		if ($migrationsDif >= 1) {
			$dbBackupId = $this->backupDatabase();
			$this->rollbackMigrations($currentRelease, $migrationsDif, $dbBackupId);
		}
		$this->symlinkRelease($previousRelease, 'Linking your previous release');
	}

	/**
	 * @param string $currentRelease
	 * @param string $previousRelease
	 * @return int
	 * @throws Exception
	 */
	private function getMigrationsDif(string $currentRelease, string $previousRelease)
	{
		$step = 'getMigrationsDif';
		$this->setStep('getMigrationsDif', function () {
			return;
		});
		$this->consoleOutput->writeln('Checking migrations diff...');
		$migrationsFolder = 'database/migrations';
		$migrations = [
			'current'  => DeployHelper::getReleaseMigrations(
				$this->config['app']['id'],
				$currentRelease . '/' . $migrationsFolder,
				$this->application
			),
			'previous' => DeployHelper::getReleaseMigrations(
				$this->config['app']['id'],
				$previousRelease . '/' . $migrationsFolder,
				$this->application
			),
		];
		$this->updateStepToSuccess($step);
		return count($migrations['current']) - count($migrations['previous']);
	}

	/**
	 * @param string $releaseFolder
	 * @param int $steps
	 * @param string $dbBackupId
	 * @throws Exception
	 */
	private function rollbackMigrations(string $releaseFolder, int $steps, string $dbBackupId)
	{
		$step = 'rollbackMigrations';
		$this->setStep($step, function () use ($dbBackupId) {
			$this->restoreDatabase($dbBackupId);
		});
		$this->appRunCommand(
			$this->config['app']['id'],
			'php ' . $releaseFolder . 'artisan migrate:rollback --force --step=' . $steps,
			'Rollback migrations to previous release'
		);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @throws Exception
	 */
	private function runCommands()
	{
		$step = 'runCommands';
		$this->setStep($step, function () {
			return;
		});
		if (!empty($this->config['commands'])) {
			foreach ($this->config['commands'] as $command) {
				if (preg_match('/artisan migrate/', $command)) {
					continue;
				}
				$this->appRunCommand(
					$this->config['app']['id'],
					'cd ' . $this->releaseFolder . ' && ' . $command,
					$command
				);
			}
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @throws Exception
	 */
	private function createSymlinkStorage()
	{
		$step = 'createSymlinkStorage';
		$this->setStep($step, function () {
			return;
		});

		$commands = [
			'delete_public_storage'            => [
				'message' => 'Removing release/public/storage',
				'execute' => function (string $message) {
					$deleteFileUrl = sprintf(
						FilesDeleteCommand::API_ENDPOINT,
						$this->config['app']['id'],
						$this->releaseFolder . 'public/storage'
					);
					$this->sendRequest($deleteFileUrl, 'DELETE', $message);
				},
			],
			'create_shared'                    => [
				'message' => 'Creating shared storage folder if not exists',
				'execute' => function (string $message) {
					$this->createRemoteIfFileNotExists('shared', $message);
				},
			],
			'copy_storage_to_shared'           => [
				'message' => 'Copying release storage to shared folder',
				'execute' => function (string $message) {
					$this->appRunCommand(
						$this->config['app']['id'],
						'cp -rv ' . $this->releaseFolder . 'storage/ shared/',
						$message
					);
				},
			],
			'symlink_shared_to_release'        => [
				'message' => 'Symlink shared storage to release',
				'execute' => function (string $message) {
					$this->appRunCommand(
						$this->config['app']['id'],
						'ln -s /var/www/shared/storage ' . $this->releaseFolder . 'storage',
						$message
					);
				},
			],
			'delete_release_storage'           => [
				'message' => 'Removing storage folder from release',
				'execute' => function (string $message) {
					$deleteFileUrl = sprintf(
						FilesDeleteCommand::API_ENDPOINT,
						$this->config['app']['id'],
						$this->releaseFolder . 'storage'
					);
					$this->sendRequest($deleteFileUrl, 'DELETE', $message);
				},
			],
			'symlink_shared_to_release_public' => [
				'message' => 'Symlink storage to public dir',
				'execute' => function (string $message) {
					$this->appRunCommand(
						$this->config['app']['id'],
						'ln -s /var/www/' . $this->releaseFolder . 'storage/app/public ' . $this->releaseFolder . 'public/storage',
						$message
					);
				},
			],
		];
		foreach ($commands as $command) {
			$command['execute']($command['message']);
		}
		$this->updateStepToSuccess($step);
	}


	/**
	 *
	 */
	private function restoreLocalEnvFile()
	{
		$step = 'restoreLocalEnvFile';
		$this->setStep($step, function () {
			return;
		});
		$this->updateEnvFile($this->localEnv);
		$this->updateStepToSuccess($step);
	}

	/**
	 *
	 */
	private function updateEnvFileToUpload()
	{
		$step = 'updateEnvFileToUpload';
		$this->setStep($step, function () {
			$this->updateEnvFile($this->localEnv);
		});
		$this->updateEnvFile($this->prepareEnvFile());
		$this->updateStepToSuccess($step);
	}

	/**
	 *
	 */
	public function revertProcess()
	{
		$this->consoleOutput->writeln('<comment>Starting revert</comment>');
		foreach (array_reverse($this->steps) as $step) {
			if ($step['status'] == 'success') {
				$step['revertFunction']();
			}
		}
	}

	/**
	 * @return array
	 */
	private function prepareEnvFile(): array
	{
		$envFromConfig = !empty($this->config['environment']) ? $this->config['environment'] : [];
		$newEnv = array_merge($envFromConfig, [
			'APP_URL'     => $this->config['app']['url'],
			'DB_HOST'     => $this->config['database']['connection']['host'],
			'DB_USERNAME' => $this->config['database']['connection']['user'],
			'DB_PASSWORD' => $this->config['database']['connection']['password'],
			'APP_ENV'     => 'production',
			'APP_DEBUG'   => false,
		]);
		return array_merge($this->localEnv, $newEnv);
	}

	/**
	 * @param array $newEnvVars
	 */
	private function updateEnvFile(array $newEnvVars)
	{
		file_put_contents($this->appPath . '.env', '');
		foreach ($newEnvVars as $key => $val) {
			file_put_contents($this->appPath . '.env', $key . '=' . $val . PHP_EOL, FILE_APPEND);
		}
	}

	/**
	 * @param string $dbBackupId
	 * @throws Exception
	 */
	private function artisanMigrate(string $dbBackupId = '')
	{
		$step = 'artisanMigrate';
		$this->setStep($step, function () use ($dbBackupId) {
			if (!empty($dbBackupId)) {
				$this->restoreDatabase($dbBackupId);
			}
		});
		$this->appRunCommand(
			$this->config['app']['id'],
			'php ' . $this->releaseFolder . 'artisan migrate --force',
			'Migrating schema'
		);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @throws Exception
	 */
	private function createDatabaseUser()
	{
		$step = 'createDatabaseUser';
		$this->setStep($step, function () {
			$command = sprintf(
				'mysql --user=root --host=%s --password=%s --execute "DROP USER \'%s\'"',
				$this->config['database']['connection']['host'],
				$this->config['database']['root_password'],
				$this->config['database']['connection']['user']
			);
			$this->appRunCommand(
				$this->config['app']['id'],
				$command,
				'Drop database user ' . $this->config['database']['connection']['user']
			);
		});
		$command = sprintf(
			'mysql --user=root --host=%s --password=%s --execute "CREATE USER \'%s\'@\'%%\' IDENTIFIED WITH mysql_native_password BY \'%s\';GRANT ALL PRIVILEGES ON * . * TO \'%s\'@\'%%\';FLUSH PRIVILEGES;"',
			$this->config['database']['connection']['host'],
			$this->config['database']['root_password'],
			$this->config['database']['connection']['user'],
			$this->config['database']['connection']['password'],
			$this->config['database']['connection']['user']
		);
		$this->appRunCommand(
			$this->config['app']['id'],
			$command,
			'Creating database user ' . $this->config['database']['connection']['user']
		);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @throws Exception
	 */
	private function initDatabase()
	{
		$progressBar = Command::getProgressBar('Initializing database', $this->consoleOutput);
		$progressBar->start();
		$dbIsRunning = false;
		while (!$dbIsRunning) {
			$dbIsRunning = $this->isDbAlreadyRunning($this->config['database']['id']);
			$progressBar->advance();
		}
		$counter = 0;
		/** We need this hack with sleep, because status of database is running,
		 * but it still not ready for connections so ~30-40 secs need to wait
		 */
		while ($counter != 50) {
			$progressBar->advance();
			$counter++;
			sleep(1);
		}
		$progressBar->finish();
		$this->consoleOutput->write(PHP_EOL);
	}

	/**
	 * @throws Exception
	 */
	private function createDatabase()
	{
		$step = 'CreateDatabase';
		$this->setStep($step, function () {
			$command = sprintf(
				'mysql --user=%s --host=%s --password=%s --execute "drop database %s;"',
				$this->config['database']['connection']['user'],
				$this->config['database']['connection']['host'],
				$this->config['database']['connection']['password'],
				getenv('DB_DATABASE')
			);
			$this->appRunCommand(
				$this->config['app']['id'],
				$command,
				'Drop database'
			);
		});

		$command = sprintf(
			'mysql --user=%s --host=%s --password=%s --execute "create database %s;"',
			$this->config['database']['connection']['user'],
			$this->config['database']['connection']['host'],
			$this->config['database']['connection']['password'],
			getenv('DB_DATABASE')
		);
		$this->appRunCommand(
			$this->config['app']['id'],
			$command,
			'Creating database `' . getenv('DB_DATABASE') . '`'
		);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @throws Exception
	 */
	private function importSqlDump()
	{
		$step = 'ImportSqlDump';
		$this->setStep($step, function () {
			return;
		});
		$this->consoleOutput->writeln('Uploading sql dump to app');
		$filesUploadCommand = $this->application->find(FilesUploadCommand::getDefaultName());
		$args = [
			'command'     => FilesUploadCommand::getDefaultName(),
			'file'        => $this->config['database']['sql_dump'],
			'app_id'      => $this->config['app']['id'],
			'remote_path' => $this->releaseFolder . self::SQL_DUMP_NAME,
			'--json'      => true,
		];
		if ($filesUploadCommand->run(new ArrayInput($args), $this->consoleOutput) == '0') {
			$command = sprintf(
				'mysql -u %s --host=%s --password=%s  %s < %s',
				$this->config['database']['connection']['user'],
				$this->config['database']['connection']['host'],
				$this->config['database']['connection']['password'],
				getenv('DB_DATABASE'),
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
	 * @param string $releaseFolder
	 * @param string $message
	 * @param bool $isFirstDeploy
	 * @throws GuzzleException
	 * @throws Exception
	 */
	private function symlinkRelease(string $releaseFolder, string $message, bool $isFirstDeploy = false)
	{
		$step = 'symlinkRelease';
		$this->setStep($step, function () {
			return;
		});
		if ($isFirstDeploy) {
			$url = sprintf(
				FilesUploadCommand::API_ENDPOINT,
				$this->config['app']['id']
			);
			$httpType = 'POST';
		} else {
			$url = sprintf(
				FilesUpdateCommand::API_ENDPOINT,
				$this->config['app']['id'],
				'public'
			);
			$httpType = 'PATCH';
		}

		$this->sendRequest($url, $httpType, $message, json_encode([
			'data' => [
				'attributes' => [
					'target'     => $releaseFolder . 'public',
					'is_dir'     => false,
					'is_symlink' => true,
				],
				'id'         => 'public',
				'type'       => 'files',
			],
		]));
		$this->updateStepToSuccess($step);
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
	 * @throws GuzzleException
	 */
	private function setUpPermissions()
	{
		$step = 'setDirectoryPermissions';
		$this->setStep($step, function () {
			return;
		});
		$directories = [
			'shared/storage',
			'shared/storage/app',
			'shared/storage/app/public',
			'shared/storage/framework',
			'shared/storage/framework/cache',
			'shared/storage/framework/sessions',
			'shared/storage/framework/views',
			'shared/storage/logs',
		];

		if (!empty($this->config['apache_permissions_dir'])) {
			$directories = array_merge($directories, array_map(function ($val) {
				return $this->releaseFolder . $val;
			}, $this->config['apache_permissions_dir']));
		}

		foreach ($directories as $directory) {
			$this->sendRequest(
				sprintf(FilesUpdateCommand::API_ENDPOINT, $this->config['app']['id'], ''),
				'PATCH',
				'Setting apache writable ' . $directory . '/',
				json_encode([
					'data' => [
						'attributes' => [
							'apache_writable' => true,
						],
						'id'         => $directory,
						'type'       => 'files',
					],
				])
			);
		}
		$this->updateStepToSuccess($step);
	}

}