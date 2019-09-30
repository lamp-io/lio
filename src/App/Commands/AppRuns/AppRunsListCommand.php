<?php


namespace Lio\App\Commands\AppRuns;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lio\App\Commands\Command;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Helper\Table;
use Art4\JsonApiClient\Serializer\ArraySerializer;

class AppRunsListCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/app_runs/';

	protected static $defaultName = 'app_runs:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return all app runs for all user\'s organizations')
			->setHelp('Get all app runs for all user\'s organizations, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_runs/appRunsList');
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

		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				self::API_ENDPOINT,
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$table = $this->getOutputAsTable($document, new Table($output));
				$table->render();
			}
		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @param Document $document
	 * @param Table $table
	 * @return Table
	 */
	protected function getOutputAsTable(Document $document, Table $table): Table
	{
		$table->setHeaderTitle('App runs list');
		$serializer = new ArraySerializer(['recursive' => true]);
		$appRuns = $serializer->serialize($document->get('data'));
		$table->setHeaders(['Id', 'App ID', 'Complete', 'Command', 'Created at']);
		$sortedData = $this->sortData($appRuns, 'created_at');
		$lastElement = end($sortedData);
		foreach ($sortedData as $key => $data) {
			$table->addRow([
				$data['id'],
				$data['attributes']['app_id'],
				$data['attributes']['complete'],
				wordwrap($data['attributes']['command'], 20, PHP_EOL),
				$data['attributes']['created_at'],
			]);
			if ($data != $lastElement) {
				$table->addRow(new TableSeparator());
			}
		}

		return $table;
	}
}
