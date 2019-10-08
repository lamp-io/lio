<?php


namespace Lio\App\Commands\Databases;

use Lio\App\AbstractCommands\AbstractDescribeCommand;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabasesDescribeCommand extends AbstractDescribeCommand
{
	protected static $defaultName = 'databases:describe';

	const API_ENDPOINT = 'https://api.lamp.io/databases/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns a database')
			->setHelp('Get database, api reference' . PHP_EOL . 'https://www.lamp.io/api#/databases/databasesShow')
			->addArgument('database_id', InputArgument::REQUIRED, 'The id of database');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('database_id')
		));
		$this->setSkipAttributes([
			'my_cnf',
		]);
		parent::execute($input, $output);
	}
}

