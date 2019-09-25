<?php

namespace Console\App\Deployers;

use Console\App\Helpers\DeployHelper;
use Dotenv\Dotenv;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

class Symfony extends DeployerAbstract
{
	const SHARED_DIRS = ['var/log', 'var/sessions'];

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
	 * @param string $appPath
	 * @param bool $isFirstDeploy
	 * @param bool $isNewDbInstance
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public function deployApp(string $appPath, bool $isFirstDeploy, bool $isNewDbInstance)
	{
		parent::deployApp($appPath, $isFirstDeploy, $isNewDbInstance);
		(Dotenv::create($this->appPath))->load();
		$zip = $this->getArtifact();
		$this->uploadArtifact($zip, $this->releaseFolder . self::ARCHIVE_NAME);
		$this->unarchiveApp($this->releaseFolder . self::ARCHIVE_NAME);
		$this->deleteArtifact($this->releaseFolder . self::ARCHIVE_NAME);
		$this->createSharedStorage($this->getSharedStorageCommands($this->getSharedDirs()));
		$this->shareEnvFile();
		$this->updateEnvFile($this->getEnvUpdates());
		$this->setUpPermissions(['var'], true);
		if ($this->isFirstDeploy) {
			$this->initSqliteDatabase();
		}
		$dbBackupId = $this->backupDatabase();
		$this->runMigrations('bin/console doctrine:migrations:migrate --no-interaction', $dbBackupId);
		$this->runCommands(['doctrine:migrations:migrate']);
		$this->symlinkRelease($this->releaseFolder . 'public', 'Linking your current release', $this->isFirstDeploy);
	}

	protected function runMigrations(string $migrationCommand, string $dbBackupId = '')
	{
		if (!$this->isDoctrineInstalled()) {
			return;
		}

		parent::runMigrations($migrationCommand, $dbBackupId);
	}

	private function getSharedDirs(): array
	{
		return array_unique(
			array_merge(
				self::SHARED_DIRS,
				!empty($this->config['shared']) ? $this->config['shared'] : []
			));
	}

	private function isDoctrineInstalled(): bool
	{
		$composerJson = json_decode(file_get_contents($this->appPath . 'composer.json'), true);
		return array_key_exists('symfony/orm-pack', $composerJson['require']);
	}

	/**
	 * @return array
	 */
	private function getEnvUpdates(): array
	{
		$env = [];
		if ($this->config['database']['system'] == 'sqlite') {
			$env = [
				'DATABASE_URL' => 'sqlite:///' . DeployHelper::SQLITE_ABSOLUTE_REMOTE_PATH,
			];
		}
		return $env;
	}

	/**
	 * @param array $dirs ;
	 * @return array
	 * @throws Exception
	 */
	private function getSharedStorageCommands(array $dirs): array
	{
		return [
			'create_shared'             => [
				'message' => 'Creating shared storage folder if not exists',
				'execute' => function (string $message) {
					$this->createFileIfNotExists('shared', $message, true);
				},
			],
			'copy_storage_to_shared'    => [
				'message' => 'Copying release %s to shared folder',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						if (file_exists($this->appPath . $dir)) {
							$this->appRunCommand(
								$this->config['app']['id'],
								'cp -rv ' . $this->releaseFolder . rtrim($dir, '/') . '/ shared/',
								sprintf($message, $dir)
							);
						}
					}
				},
			],
			'delete_release_storage'    => [
				'message' => 'Removing %s from release',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						if (file_exists($this->appPath . $dir)) {
							$this->appRunCommand(
								$this->config['app']['id'],
								'rm -rf ' . $this->releaseFolder . rtrim($dir, '/') . '/',
								sprintf($message, $dir)
							);
						}
					}
				},
			],
			'symlink_shared_to_release' => [
				'message' => 'Symlink %s to release',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						if (file_exists($this->appPath . $dir)) {
							$dirName = explode('/', $dir);
							$dirName = $dirName[count($dirName) - 1];
							$this->appRunCommand(
								$this->config['app']['id'],
								'ln -s /var/www/shared/' . rtrim($dirName, '/') . ' ' . $this->releaseFolder . rtrim($dir, '/'),
								sprintf($message, $dir)
							);
						}
					}
				},
			],
			'give_permissions'          => [
				'message' => 'Apache can write in shared/%s',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						if (file_exists($this->appPath . $dir)) {
							$dirName = explode('/', $dir);
							$dirName = $dirName[count($dirName) - 1];
							$this->giveFileApachePermission(
								'shared/' . rtrim($dirName, '/'),
								$message,
								true
							);
						}
					}
				},
			],
		];
	}
}