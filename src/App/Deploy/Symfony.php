<?php

namespace Console\App\Deploy;

use Console\App\Commands\Files\FilesUpdateCommand;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

class Symfony extends DeployAbstract
{
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
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public function deployApp(string $appPath, bool $isFirstDeploy)
	{
		parent::deployApp($appPath, $isFirstDeploy);
		$zip = $this->getZipApp();
		$this->uploadToApp($zip, $this->releaseFolder . self::ARCHIVE_NAME);
		$this->unarchiveApp($this->releaseFolder . self::ARCHIVE_NAME);
		$this->deleteArchiveRemote($this->releaseFolder . self::ARCHIVE_NAME);
		$this->setUpPermissions([$this->releaseFolder . 'var'], true);
		$this->createSharedStorage($this->getSharedStorageCommands($this->getSharedDirs()));
		if ($this->isFirstDeploy) {
			$this->initSqliteDatabase();
		}
		$this->runCommands();
		$this->deleteArchiveLocal();
		$dbBackupId = $this->backupDatabase();
		$this->runMigrations('bin/console doctrine:migrations:migrate --no-interaction', $dbBackupId);
		$this->symlinkRelease($this->releaseFolder . 'public', 'Linking your current release', $this->isFirstDeploy);
	}

	protected function getSharedDirs(): array
	{
		return array_unique(array_merge(['var/log', 'var/sessions'], $this->config['shared']));
	}

	/**
	 * @param array $dirs ;
	 * @return array
	 * @throws Exception
	 */
	protected function getSharedStorageCommands(array $dirs): array
	{
		return [
			'create_shared'             => [
				'message' => 'Creating shared storage folder if not exists',
				'execute' => function (string $message) {
					$this->createRemoteIfFileNotExists('shared', $message);
				},
			],
			'copy_storage_to_shared'    => [
				'message' => 'Copying release %s to shared folder',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						$this->appRunCommand(
							$this->config['app']['id'],
							'cp -rv ' . $this->releaseFolder . rtrim($dir, '/') . '/ shared/',
							sprintf($message, $dir)
						);
					}
				},
			],
			'delete_release_storage'    => [
				'message' => 'Removing %s from release',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						$this->appRunCommand(
							$this->config['app']['id'],
							'rm -rf ' . $this->releaseFolder . rtrim($dir, '/') . '/',
							sprintf($message, $dir)
						);
					}
				},
			],
			'symlink_shared_to_release' => [
				'message' => 'Symlink %s to release',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						$dirName = explode('/', $dir);
						$dirName = $dirName[count($dirName) - 1];
						$this->appRunCommand(
							$this->config['app']['id'],
							'ln -s /var/www/shared/' . rtrim($dirName, '/') . ' ' . $this->releaseFolder . rtrim($dir, '/'),
							sprintf($message, $dir)
						);
					}
				},
			],
			'give_permissions'          => [
				'message' => 'Apache can write in shared storage',
				'execute' => function (string $message) {
					$fileUpdateUrl = sprintf(
						sprintf(
							FilesUpdateCommand::API_ENDPOINT . '?recur=true',
							$this->config['app']['id'],
							'shared/storage'
						)
					);
					$this->sendRequest($fileUpdateUrl, 'PATCH', $message, json_encode([
						'data' => [
							'attributes' => [
								'apache_writable' => true,
							],
							'id'         => 'shared',
							'type'       => 'files',
						],
					]));
				},
			],
		];
	}

}