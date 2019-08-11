<?php

namespace Console\App\Deploy;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use Console\App\Commands\Databases\DatabasesDescribeCommand;
use Console\App\Commands\Files\FilesUpdateCommand;
use Dotenv\Dotenv;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class Laravel extends DeployAbstract
{
	/**
	 * @var array
	 */
	private $localEnv = [];

	/**
	 * Laravel constructor.
	 * @param string $appPath
	 * @param Application $application
	 * @param array $config
	 * @param bool $isFirstDeploy
	 */
	public function __construct(string $appPath, Application $application, array $config, bool $isFirstDeploy)
	{
		parent::__construct($appPath, $application, $config, $isFirstDeploy);
		(Dotenv::create($this->appPath))->load();
		$this->localEnv = $_ENV;
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function deployApp()
	{
		$this->updateEnvFileToUpload();
		$zip = $this->getZipApp();
		if ($this->isFirstDeploy) {
			$this->clearApp();
		}
		$this->restoreLocalEnvFile();
		$this->uploadToApp($zip, $this->releaseFolder . self::ARCHIVE_NAME);
		$this->unarchiveApp($this->releaseFolder . self::ARCHIVE_NAME);
		$this->setUpPermissions();
		$this->deleteArchiveRemote($this->releaseFolder . self::ARCHIVE_NAME);
		if ($this->isFirstDeploy) {
			$this->createDatabase();
			if (!empty($this->config['database']['sql_dump'])) {
				$this->importSqlDump();
			}
			$dbBackupId = '';
		} else {
			$dbBackupId = $this->backupDatabase();
		}
		$this->artisanMigrate($dbBackupId);
		$this->createSymlink();
		$this->deleteArchiveLocal();
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
	public function revert()
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
			'php ' . $this->releaseFolder . 'artisan migrate',
			'Migrating schema'
		);
		$this->updateStepToSuccess($step);
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
		$this->consoleOutput->write(PHP_EOL);
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
		$this->uploadToApp(
			$this->config['database']['sql_dump'],
			$this->releaseFolder . self::SQL_DUMP_NAME
		);

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

	}

	/**
	 * @throws Exception
	 */
	private function createSymlink()
	{
		$step = 'createSymlink';
		$this->setStep($step, function () {
			return;
		});
		$symLinkOptions = ($this->isFirstDeploy) ? '-s' : '-sfn';
		$command = 'ln ' . $symLinkOptions . ' ' . $this->releaseFolder . 'public public';
		$this->appRunCommand(
			$this->config['app']['id'],
			$command,
			'Linking your current release'
		);
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
	 * @throws Exception
	 */
	private function setUpPermissions()
	{
		$step = 'setDirectoryPermissions';
		$this->setStep($step, function () {
			return;
		});
		$directories = [
			$this->releaseFolder . 'storage/logs',
			$this->releaseFolder . 'storage/framework/sessions',
			$this->releaseFolder . 'storage/framework/views',
		];
		foreach ($directories as $directory) {
			$appRunsDescribeCommand = $this->application->find(FilesUpdateCommand::getDefaultName());
			$args = [
				'command'     => FilesUpdateCommand::getDefaultName(),
				'app_id'      => $this->config['app']['id'],
				'remote_path' => $directory,
				'--json'      => true,
			];
			if ($appRunsDescribeCommand->run(new ArrayInput($args), $this->consoleOutput) != '0') {
				throw new Exception('Cant set appache writable for ' . $directory);
			}
		}
		$this->updateStepToSuccess($step);
	}

}