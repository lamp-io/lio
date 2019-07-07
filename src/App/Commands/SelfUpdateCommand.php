<?php

namespace Console\App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Humbug\SelfUpdate\Updater;
use Humbug\SelfUpdate\Strategy\GithubStrategy;

class SelfUpdateCommand extends Command
{
	const PACKAGE_NAME = 'lamp-io/lio';
	const FILE_NAME = 'lio.phar';

	protected static $defaultName = 'self-update';

	/**
	 * @var OutputInterface
	 */
	protected $output;
	/**
	 * @var string
	 */
	protected $version;

	/**
	 * Execute the command.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->output = $output;
		$this->version = $this->getApplication()->getVersion();
		$updater = new Updater(null, false);
		$updater->setStrategy(Updater::STRATEGY_GITHUB);
		$this->update($this->getGithubReleasesUpdater($updater));
	}


	protected function getGithubReleasesUpdater(Updater $updater)
	{
		/** @var GithubStrategy $strategy */
		$strategy = $updater->getStrategy();
		$strategy->setPackageName(self::PACKAGE_NAME);
		$strategy->setPharName(self::FILE_NAME);
		$strategy->setCurrentLocalVersion($this->version);
		return $updater;
	}


	protected function update(Updater $updater)
	{
		$this->output->writeln('Updating...' . PHP_EOL);
		try {
			$result = $updater->update();
			$newVersion = $updater->getNewVersion();
			$oldVersion = $updater->getOldVersion();
			if (strlen($newVersion) == 40) {
				$newVersion = 'dev-' . $newVersion;
			}
			if (strlen($oldVersion) == 40) {
				$oldVersion = 'dev-' . $oldVersion;
			}

			if ($result) {
				$this->output->writeln('<fg=green>Lio cli has been updated.</fg=green>');
				$this->output->writeln(sprintf(
					'<fg=green>Current version is:</fg=green> <options=bold>%s</options=bold>.',
					$newVersion
				));
				$this->output->writeln(sprintf(
					'<fg=green>Previous version was:</fg=green> <options=bold>%s</options=bold>.',
					$oldVersion
				));
			} else {
				$this->output->writeln('<fg=green>Lio cli currently up to date.</fg=green>');
				$this->output->writeln(sprintf(
					'<fg=green>Current version is:</fg=green> <options=bold>%s</options=bold>.',
					$oldVersion
				));
			}
		} catch (\Exception $e) {
			$this->output->writeln(sprintf('Error: <fg=yellow>%s</fg=yellow>', $e->getMessage()));
		}
		$this->output->write(PHP_EOL);
		$this->output->writeln('You can also select update stability using --dev, --pre (alpha/beta/rc) or --stable.');
	}

	protected function configure()
	{
		$this
			->setName('self-update')
			->setDescription('Update lio.phar to most recent stable release');
	}
}