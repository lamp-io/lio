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
				$serializer = new ArraySerializer(['recursive' => true]);
				$logsSortedByPods = $this->sortByPods($serializer->serialize($document));
				foreach ($logsSortedByPods as $podName => $podLogs) {
					$table = $this->getOutputAsTable($podLogs, $podName, new Table($output));
					$table->render();
				}
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
	 * @param array $logs
	 * @return array
	 */
	protected function sortByPods(array $logs): array
	{
		$sortedByPods = [];
		foreach ($logs['data'] as $key => $data) {
			$rows = [];
			foreach ($data['attributes'] as $attributeKey => $attribute) {
				if (empty($attribute)) {
					continue;
				}
				$rows[$attributeKey] = trim(preg_replace(
						'/\s\s+|\t/', ' ', $attribute
					)) . PHP_EOL;
			}
			$sortedByPods[$rows['pod_name']][] = [
				'pod_name'  => trim($rows['pod_name']),
				'timestamp' => trim($rows['timestamp']),
				'payload'   => trim($rows['payload']),
			];
		}
		return $sortedByPods;
	}


	/**
	 * @param array $podLogs
	 * @param string $podName
	 * @param Table $table
	 * @return Table
	 */
	protected function getOutputAsTable(array $podLogs, string $podName, Table $table): Table
	{
		$table->setHeaderTitle('Pod ' . trim($podName, PHP_EOL));
		$table->setHeaders([
			'Timestamp', 'Payload',
		]);
		foreach ($podLogs as $key => $data) {
			$table->addRow([$data['timestamp'], $data['payload']]);
		}
		return $table;
	}
}