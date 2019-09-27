<?php


namespace Console\App\Commands\Databases;

use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabasesDeleteCommand extends Command
{
	protected static $defaultName = 'databases:delete';

	const API_ENDPOINT = 'https://api.lamp.io/databases/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete a database')
			->setHelp('Delete a database, api reference' . PHP_EOL . 'https://www.lamp.io/api#/databases/databasesDelete')
			->addArgument('database_id', InputArgument::REQUIRED, 'The id of database')
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
		if (!$this->askConfirm('<info>Are you sure you want to delete database? (y/N)</info>', $output, $input)) {
			return 0;
		}
		try {
			$this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('database_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
			if (empty($input->getOption('json'))) {
				$output->writeln('<info>Database ' . $input->getArgument('database_id') . ' successfully deleted</info>');
			}
		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		} catch (InvalidArgumentException $invalidArgumentException) {
			$output->writeln($invalidArgumentException->getMessage());
			return 1;
		}

	}
}