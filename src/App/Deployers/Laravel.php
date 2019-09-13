<?php

namespace Console\App\Deployers;

use Console\App\Commands\Files\FilesDeleteCommand;
use Console\App\Commands\Files\FilesUpdateCommand;
use Console\App\Helpers\DeployHelper;
use Dotenv\Dotenv;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class Laravel extends DeployerAbstract
{

	const SKIP_COMMANDS = [
		'artisan migrate',
	];

	const SHARED_DIR = ['storage'];

	/**
	 * @param string $appPath
	 * @param bool $isFirstDeploy
	 * @param bool $isNewDbInstance
	 * @return void
	 * @throws Exception
	 * @throws GuzzleException
	 */
	public function deployApp(string $appPath, bool $isFirstDeploy, bool $isNewDbInstance)
	{
		parent::deployApp($appPath, $isFirstDeploy, $isNewDbInstance);
		(Dotenv::create($this->appPath))->load();
		$zip = $this->getZipApp();
		$this->uploadToApp($zip, $this->releaseFolder . self::ARCHIVE_NAME);
		$this->unarchiveApp($this->releaseFolder . self::ARCHIVE_NAME);
		$this->deleteArchiveRemote($this->releaseFolder . self::ARCHIVE_NAME);
		$this->createSharedStorage($this->getSharedStorageCommands($this->getSharedDirs()));
		$this->shareEnvFile();
		$this->updateEnvFile($this->getEnvUpdates());
		$this->setUpPermissions();
		if ($this->isFirstDeploy && $this->config['database']['system'] == 'sqlite') {
			$this->initSqliteDatabase();
		} elseif ($this->isFirstDeploy && $this->isNewDbInstance) {
			$this->initDatabase($this->config['database']['id']);
			$this->createDatabase(
				$this->config['database']['id'],
				getenv('DB_DATABASE'),
				DeployHelper::DB_USER,
				$this->config['database']['root_password']
			);
		}
		if (!empty($this->config['database']['sql_dump']) && ($this->isNewDbInstance || $this->config['database']['type'] == 'external' && $this->isFirstDeploy)) {
			$this->importSqlDump(
				$this->config['database']['type'] == 'external' ? getenv('DB_HOST') : $this->config['database']['id'],
				getenv('DB_DATABASE'),
				$this->config['database']['type'] == 'external' ? getenv('DB_USERNAME') : DeployHelper::DB_USER,
				$this->config['database']['type'] == 'external' ? getenv('DB_PASSWORD') : $this->config['database']['root_password']
			);
		}
		$dbBackupId = ($this->isFirstDeploy) ? '' : $this->backupDatabase();
		$this->deleteArchiveLocal();
		$this->runMigrations('artisan migrate --force', $dbBackupId);
		$this->runCommands(self::SKIP_COMMANDS);
		$this->symlinkRelease($this->releaseFolder . 'public', 'Linking your current release', $this->isFirstDeploy);
	}

	private function getSharedDirs(): array
	{
		return array_merge(
			self::SHARED_DIR,
			!empty($this->config['shared']) ? $this->config['shared'] : []
		);
	}

	/**
	 * @param array $dirs
	 * @return array
	 * @throws Exception
	 */
	protected function getSharedStorageCommands(array $dirs): array
	{
		return [
			'delete_public_storage'            => [
				'message' => 'Removing release/public/storage',
				'execute' => function (string $message) {
					$deleteFileUrl = sprintf(
						FilesDeleteCommand::API_ENDPOINT,
						$this->config['app']['id'],
						$this->releaseFolder . 'public/storage'
					);
					try {
						$this->sendRequest($deleteFileUrl, 'DELETE', $message);
					} catch (ClientException $clientException) {
						$this->consoleOutput->write(PHP_EOL);
						return;
					}
				},
			],
			'create_shared'                    => [
				'message' => 'Creating shared storage folder if not exists',
				'execute' => function (string $message) {
					$this->createRemoteIfFileNotExists('shared', $message);
				},
			],
			'copy_storage_to_shared'           => [
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
			'delete_release_storage'           => [
				'message' => 'Removing %s folder from release',
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
			'symlink_shared_to_release'        => [
				'message' => 'Symlink shared %s to release',
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
			'give_permissions'                 => [
				'message' => 'Apache can write in shared/%s',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						$dirName = explode('/', $dir);
						$dirName = $dirName[count($dirName) - 1];
						$fileUpdateUrl = sprintf(
							sprintf(
								FilesUpdateCommand::API_ENDPOINT . '?recur=true',
								$this->config['app']['id'],
								'shared/' . rtrim($dirName, '/')
							)
						);
						$this->sendRequest($fileUpdateUrl, 'PATCH', sprintf($message, $dir), json_encode([
							'data' => [
								'attributes' => [
									'apache_writable' => true,
								],
								'id'         => 'shared/' . rtrim($dirName, '/'),
								'type'       => 'files',
							],
						]));
					}
				},
			],
		];
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
	private function getEnvUpdates(): array
	{
		$env = [];
		if ($this->isNewDbInstance && $this->isFirstDeploy && $this->config['database']['type'] != 'external') {
			$env = [
				'DB_HOST'     => $this->config['database']['id'],
				'DB_PORT'     => DeployHelper::DB_PORT,
				'DB_USERNAME' => DeployHelper::DB_USER,
				'DB_PASSWORD' => $this->config['database']['root_password'],
			];
		} elseif ($this->isFirstDeploy && $this->config['database']['type'] == 'external') {
			$env = [
				'DB_HOST'     => getenv('DB_HOST'),
				'DB_PORT'     => getenv('DB_PORT'),
				'DB_USERNAME' => getenv('DB_USERNAME'),
				'DB_PASSWORD' => getenv('DB_PASSWORD'),
			];
		} elseif ($this->isFirstDeploy && $this->config['database']['system'] == 'sqlite') {
			$env = [
				'DB_DATABASE' => DeployHelper::SQLITE_ABSOLUTE_REMOTE_PATH,
			];
		}

		if ($this->isFirstDeploy) {
			$env = array_merge($env, [
				'APP_URL' => !empty($this->config['app']['attributes']['hostname']) ? 'https://' . $this->config['app']['attributes']['hostname'] : 'https://' . $this->config['app']['id'] . '.lamp.app/',
			]);
		}
		return $env;
	}
}