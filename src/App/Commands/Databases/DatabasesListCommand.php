<?php


namespace Lio\App\Commands\Databases;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Lio\App\Console\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class DatabasesListCommand extends Command
{
	protected static $defaultName = 'databases:list';

	const API_ENDPOINT = 'https://api.lamp.io/databases%s';

	const OPTIONS_TO_QUERY_KEYS = [
		'organization_id' => 'filter[organization_id]',
	];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns all databases')
			->setHelp('Get all databases, api reference' . PHP_EOL . 'https://www.lamp.io/api#/databases/databasesList')
			->addOption('organization_id', null, InputOption::VALUE_REQUIRED, 'Filter output by organization id value');
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
		$progressBar = self::getProgressBar(
			'Getting databases',
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				sprintf(self::API_ENDPOINT,
					$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_TO_QUERY_KEYS)
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
					'progress'  => function () use ($progressBar) {
						$progressBar->advance();
					},
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->write(PHP_EOL);
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$table = $this->getOutputAsTable($document, new Table($output));
				$table->render();
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
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
		$table->setHeaderTitle('Database');
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$header = ['Id', 'Attributes'];
		$table->setHeaders($header);
		$sortedData = $this->sortData($serializedDocument['data'], 'updated_at');
		$lastElement = end($sortedData);
		foreach ($sortedData as $key => $data) {
			$row = [$data['id']];
			$attributeArray = [];
			foreach ($data['attributes'] as $attributeKey => $attribute) {
				if ($attributeKey == 'my_cnf' && !empty($attribute)) {
					$mysqlConfig = $attributeKey . ' : ' . wordwrap(trim(preg_replace(
							'/\s\s+|\t/', ' ', $attribute
						)), 40);
				} else {
					$attributeArray[] = $attributeKey . ' : ' . $attribute;
				}
			}
			$attributes = implode(PHP_EOL, $attributeArray);
			$attributes .= !empty($mysqlConfig) ? PHP_EOL . $mysqlConfig : '';
			unset($mysqlConfig);
			$row[] = $attributes;
			$table->addRow($row);
			if ($data != $lastElement) {
				$table->addRow(new TableSeparator());
			}

		}

		return $table;

	}
}