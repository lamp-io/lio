<?php

namespace Console\App\Deploy;

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
		$this->setUpPermissions([$this->releaseFolder . 'var'], [], true);
		$this->symlinkRelease($this->releaseFolder . 'public', 'Linking your current release', $this->isFirstDeploy);
		$this->deleteArchiveLocal();
	}

}