<?php

namespace Lio\App\Commands\DbBackups;

use Art4\JsonApiClient\Document;
use Art4\JsonApiClient\Helper\Parser;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbBackupsNewCommand extends AbstractNewCommand
{
	protected static $defaultName = 'db_backups:new';

	const API_ENDPOINT = 'https://api.lamp.io/db_backups';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Back up database')
			->setHelp('Backup database, api reference' . PHP_EOL . 'https://www.lamp.io/api#/db_backups/dbBackupsCreate')
			->addArgument('database_id', InputArgument::REQUIRED, 'The id of database')
			->setApiEndpoint(self::API_ENDPOINT);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$output->writeln(
			'<info>Backuping database, with id' . $document->get('data.attributes.database_id') . 'started' . PHP_EOL .
			'Backup id: ' . $document->get('data.id') . '</info>'
		);
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
					'database_id' => $input->getArgument('database_id'),
				],
				'type'       => 'db_backups',
			],
		]);
	}
}