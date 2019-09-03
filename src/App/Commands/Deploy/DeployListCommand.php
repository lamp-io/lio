<?php

namespace Console\App\Commands\Deploy;

use Console\App\Commands\Command;
use Console\App\Helpers\DeployHelper;
use Exception;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployListCommand extends Command
{
	protected static $defaultName = 'deploy:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Get list of available deploys')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->setHelp('try rebooting');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		if (!DeployHelper::isReleasesFolderExists($input->getArgument('app_id'), $this->getApplication())) {
			throw new Exception('Your app hasn\'t not contain any deploys yet');
		}
		$releases = DeployHelper::getReleases($input->getArgument('app_id'), $this->getApplication());
		$activeRelease = DeployHelper::getActiveRelease($input->getArgument('app_id'), $this->getApplication());
		$table = $this->getOutputAsTable($releases, new Table($output), $activeRelease);
		$table->render();

	}

	/**
	 * @param array $releases
	 * @param Table $table
	 * @param string $activeRelease
	 * @return Table
	 * @throws Exception
	 */
	protected function getOutputAsTable(array $releases, Table $table, string $activeRelease): Table
	{
		$table->setHeaderTitle('Releases');
		$table->setHeaders(['№', 'Date', 'Is active']);
		foreach ($releases as $key => $release) {
			$releaseDate = [];
			preg_match('/[0-9]*$/', $release['id'], $releaseDate);
			if (!empty($releaseDate[0])) {
				$formattedDate = date('Y-m-d H:i:s', strtotime($releaseDate[0]));
				$isActive = strpos($release['id'], $activeRelease) !== false;
				$table->addRow([
					$key + 1,
					$formattedDate,
					$isActive ? 'true' : '',
				]);
				if ($key != count($releases) - 1) {
					$table->addRow(new TableSeparator());
				}
			}
		}
		return $table;
	}
}