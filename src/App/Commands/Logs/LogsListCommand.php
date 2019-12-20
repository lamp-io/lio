<?php

namespace Lio\App\Commands\Logs;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractListCommand;
use Exception;
use Lio\App\Helpers\CommandsHelper;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LogsListCommand extends AbstractListCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/logs%s';

	const OPTIONS_TO_QUERY_KEYS = [
		'organization_id' => 'filter[organization_id]',
		'pod_name'        => 'filter[pod_name]',
		'start_time'      => 'filter[start_time]',
		'end_time'        => 'filter[end_time]',
	];

	/**
	 * @var string
	 */
	protected static $defaultName = 'logs:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return logs')
			->setHelp('Get logs, api reference' . PHP_EOL . 'https://www.lamp.io/api#/logs/logsList')
			->addOption('organization_id', 'o', InputOption::VALUE_REQUIRED, 'One organization_id. If omitted defaults to user\'s default organization')
			->addOption('pod_name', 'p', InputOption::VALUE_REQUIRED, 'One pod_name. Uses wildcard prefix match')
			->addOption('start_time', null, InputOption::VALUE_REQUIRED, 'Start time conforming to RFC3339. Defaults to 10 minutes in the past')
			->addOption('end_time', null, InputOption::VALUE_REQUIRED, 'End time conforming to RFC3339. Defaults to now');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_TO_QUERY_KEYS)
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
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$sortedData = CommandsHelper::sortData($serializedDocument['data'], 'timestamp');
		$table = $this->getTableOutput(
			$sortedData,
			$document,
			'Logs',
			[
				'Timestamp' => 'data.%d.attributes.timestamp',
				'Pod name'  => 'data.%d.attributes.pod_name',
				'Payload'   => 'data.%d.attributes.payload',
			],
			new Table($output),
			end($sortedData) ? end($sortedData) : [],
			80
		);
		$table->render();
	}
}