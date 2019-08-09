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
	private $localEnv = [];

	private $config = [];

	public function __construct(string $appPath, Application $application, int $releaseId)
	{
		parent::__construct($appPath, $application, $releaseId);
		(Dotenv::create($this->appPath))->load();
		$this->localEnv = $_ENV;
	}

	/**
	 * @param string $appId
	 * @param bool $isNewApp
	 * @param bool $isNewDatabase
	 * @param array $config
	 * @throws Exception
	 */
	public function deployApp(string $appId, bool $isNewApp, bool $isNewDatabase, array $config)
	{
		$this->config = $config;
		$this->updateEnvFile($this->prepareEnvFile());
		$zip = $this->getZipApp();
		if ($isNewApp) {
			$this->clearApp($appId);
		}
		$this->updateEnvFile($this->localEnv);
		$this->uploadToApp($appId, $zip, $this->releaseFolder . self::ARCHIVE_NAME);
		$this->unarchiveApp($appId, $this->releaseFolder . self::ARCHIVE_NAME);
		$this->setUpPermissions($appId);
		$this->deleteArchive($appId, $this->releaseFolder . self::ARCHIVE_NAME);
		$this->setUpDatabase($appId, $isNewDatabase);
		$this->createSymlink($appId, $isNewApp);
		unlink($this->appPath . self::ARCHIVE_NAME);
	}

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
	 * @param string $appId
	 * @param bool $isNewDatabase
	 * @throws Exception
	 */
	private function setUpDatabase(string $appId, bool $isNewDatabase)
	{
		if ($isNewDatabase) {
			$this->setUpNewDatabase($appId);
		} else {
			$this->appRunCommand(
				$appId,
				'php ' . $this->releaseFolder . 'artisan migrate',
				'Migrating schema'
			);
		}
	}

	/**
	 * @param string $appId
	 * @throws Exception
	 */
	private function setUpNewDatabase(string $appId)
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
		$command = sprintf(
			'mysql --user=%s --host=%s --password=%s --execute "create database %s;"',
			$this->config['database']['connection']['user'],
			$this->config['database']['connection']['host'],
			$this->config['database']['connection']['password'],
			getenv('DB_DATABASE')
		);
		$this->appRunCommand(
			$appId,
			$command,
			'Creating database `' . getenv('DB_DATABASE') . '`'
		);
		if (!empty($this->config['database']['sql_dump'])) {
			$this->consoleOutput->writeln('Uploading sql dump to app');
			$this->uploadToApp(
				$appId,
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
				$appId,
				$command,
				'Importing sql dump'
			);
		} else {
			$this->appRunCommand(
				$appId,
				'php ' . $this->releaseFolder . 'artisan migrate',
				'Migrating schema'
			);
		}
	}

	/**
	 * @param string $appId
	 * @param bool $isNewApp
	 * @throws Exception
	 */
	private function createSymlink(string $appId, bool $isNewApp)
	{
		$symLinkOptions = ($isNewApp) ? '-s' : '-sfn';
		$command = 'ln ' . $symLinkOptions . ' ' . $this->releaseFolder . 'public public';
		$this->appRunCommand(
			$appId,
			$command,
			'Linking your current release'
		);
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
		$appRunsNewCommand->run(new ArrayInput($args), $bufferOutput);
		/** @var Document $document */
		$document = Parser::parseResponseString($bufferOutput->fetch());

		return $document->get('data.attributes.status') === 'running';
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