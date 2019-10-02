<?php

namespace Lio\App\Commands\DbBackups;

use Lio\App\Console\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class DbBackupsDeleteCommand extends Command
{
	protected static $defaultName = 'db_backups:delete';

	const API_ENDPOINT = 'https://api.lamp.io/db_backups/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete a db backup')
			->setHelp('Delete a db backup, api reference' . PHP_EOL . 'https://www.lamp.io/api#/db_backups/dbBackupsDelete')
			->addArgument('db_backup_id', InputArgument::REQUIRED, 'The ID of the db backup')
			->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirm delete question');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		if (!$this->askConfirm('<info>Are you sure you want to delete db backup? (y/N)</info>', $output, $input)) {
			return 0;
		}
		$progressBar = self::getProgressBar(
			'Deleting database backup ' . $input->getArgument('db_backup_id'),
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$response = $this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('db_backup_id')
				),
				[
					'headers'  => $this->httpHelper->getHeaders(),
					'progress' => function () use ($progressBar) {
						$progressBar->advance();
					},
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->write(PHP_EOL);
				$output->writeln(
					'<info>Database backup deleted ' . $input->getArgument('db_backup_id') . '</info>'
				);
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}
}