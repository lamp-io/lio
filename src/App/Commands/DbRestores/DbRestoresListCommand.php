<?php

namespace Lio\App\Commands\DbRestores;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractListCommand;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Lio\App\Helpers\CommandsHelper;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DbRestoresListCommand extends AbstractListCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/db_restores%s';

	const OPTIONS_TO_QUERY_KEYS = [
		'organization_id' => 'filter[organization_id]',
	];

	/**
	 * @var string
	 */
	protected static $defaultName = 'db_restores:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return db restore jobs')
			->setHelp('Get db restore jobs, api reference' . PHP_EOL . 'https://www.lamp.io/api#/db_restores/dbRestoresList')
			->addOption('organization_id', 'o', InputOption::VALUE_REQUIRED, 'Comma-separated list of requested organization_ids. If omitted defaults to user\'s default organization');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_TO_QUERY_KEYS)
		));
		parent::execute($input, $output);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$sortedData = CommandsHelper::sortData($serializedDocument['data'], 'created_at');
		$table = $this->getTableOutput(
			$sortedData,
			$document,
			'Database restores',
			[
				'Id'              => 'data.%d.id',
				'Backup Id'       => 'data.%d.attributes.db_backup_id',
				'Database Id'     => 'data.%d.attributes.target_database_id',
				'Created at'      => 'data.%d.attributes.created_at',
				'Organization Id' => 'data.%d.attributes.organization_id',
				'Status'          => 'data.%d.attributes.status',
			],
			new Table($output),
			end($sortedData)
		);
		$table->render();
	}
}