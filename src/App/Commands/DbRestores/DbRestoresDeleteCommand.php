<?php

namespace Lio\App\Commands\DbRestores;

use Lio\App\AbstractCommands\AbstractDeleteCommand;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbRestoresDeleteCommand extends AbstractDeleteCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/db_restores/%s';
	
	protected static $defaultName = 'db_restores:delete';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete a db restore job')
			->setHelp('Db restore job, api reference' . PHP_EOL . 'https://www.lamp.io/api#/db_restores/dbRestoresDelete')
			->addArgument('db_restore_id', InputArgument::REQUIRED, 'The ID of the db restore');
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
			$input->getArgument('db_restore_id')
		));
		return parent::execute($input, $output);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		$output->writeln('<info>Db restore ' . $input->getArgument('db_restore_id') . ' has been deleted</info>');
	}
}