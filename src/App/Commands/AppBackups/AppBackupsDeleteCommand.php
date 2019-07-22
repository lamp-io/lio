<?php

namespace Console\App\Commands\AppBackups;

use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppBackupsDeleteCommand extends Command
{
	protected static $defaultName = 'app_backups:delete';

	const API_ENDPOINT = 'https://api.lamp.io/app_backups/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete an app backup')
			->setHelp('https://www.lamp.io/api#/app_backups/appBackupsShow')
			->addArgument('app_backup_id', InputArgument::REQUIRED, 'The ID of the app backup');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_backup_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]

			);
			$output->writeln(
				'<info>Backup deleted ' . $input->getArgument('app_backup_id') . '</info>'
			);

		} catch (GuzzleException $guzzleException) {
			$output->writeln('<error>' . $guzzleException->getMessage() . '</error>');
			die();
		}
	}
}