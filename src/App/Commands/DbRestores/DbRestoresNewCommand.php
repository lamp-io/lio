<?php

namespace Lio\App\Commands\DbRestores;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Lio\App\Console\Command;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbRestoresNewCommand extends AbstractNewCommand
{
	protected static $defaultName = 'db_restores:new';

	const API_ENDPOINT = 'https://api.lamp.io/db_restores';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Create db restore job (restore a db backup to a database)')
			->setHelp('Restore a db backup to a database, api reference' . PHP_EOL . 'https://www.lamp.io/api#/db_restores/dbRestoresCreate')
			->addArgument('database_id', InputArgument::REQUIRED, 'The id of database')
			->addArgument('db_backup_id', InputArgument::REQUIRED, 'The ID of the db backup')
			->setApiEndpoint(self::API_ENDPOINT);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @throws Exception
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$dbRestoreId = $document->get('data.id');
		$progressBar = Command::getProgressBar('Restoring database ' . $document->get('data.attributes.target_database_id'), $output);
		$progressBar->start();
		while (!DbRestoresDescribeCommand::isDbRestoreCompleted($dbRestoreId, $this->getApplication())) {
			$progressBar->advance();
		}
		$progressBar->finish();
		$output->write(PHP_EOL);
		$output->writeln('<info>Restore finished for database, ' . $document->get('data.attributes.target_database_id') . '</info>');
	}


	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'db_backup_id'       => $input->getArgument('db_backup_id'),
					'target_database_id' => $input->getArgument('database_id'),
				],
				'type'       => 'db_restores',
			],
		]);
	}
}