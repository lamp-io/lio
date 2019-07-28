<?php

namespace Console\App\Commands\Logs;

use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LogsListCommand extends Command
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
			->setHelp('https://www.lamp.io/api#/logs/logsList')
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
		parent::execute($input, $output);

		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				sprintf(
					self::API_ENDPOINT,
					$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_TO_QUERY_KEYS)
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$this->getOutputAsTable($document, $output);
			}
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
			exit(1);
		}
	}

	/**
	 * @param Document $document
	 * @param OutputInterface $output
	 */
	protected function getOutputAsTable(Document $document, OutputInterface $output)
	{
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		foreach ($serializedDocument['data'] as $key => $data) {
			$rows = [];
			$headers = [];
			foreach ($data['attributes'] as $attributeKey => $attribute) {
				array_push($headers, $attributeKey);
				$rows[] = trim(preg_replace(
						'/\s\s+|\t/', ' ', wordwrap($attribute, 40)
					)) . PHP_EOL;
			}
			$table = new Table($output);
			$table->setHeaderTitle('Logs ' . $data['id']);
			$table->setHeaders($headers);
			$table->addRow($rows);
			$table->render();
			unset($rows, $headers, $table);
		}
	}
}