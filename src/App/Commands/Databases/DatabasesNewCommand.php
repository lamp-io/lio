<?php

namespace Console\App\Commands\Databases;

use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Helper\Table;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\Helper\Parser;

class DatabasesNewCommand extends Command
{
	protected static $defaultName = 'databases:new';

	const API_ENDPOINT = 'https://api.lamp.io/databases';

	const EXCLUDE_FROM_OUTPUT = [
		'my_cnf'
	];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Create a new database')
			->setHelp('https://www.lamp.io/api#/databases/databasesCreate')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'Description of your database', '')
			->addOption('memory', 'm', InputOption::VALUE_REQUIRED, 'Amount of virtual memory on your database, default 512Mi', '512Mi')
			->addOption('organization_id', null, InputOption::VALUE_REQUIRED, 'Name of your organization', '')
			->addOption('my_cnf', null, InputOption::VALUE_REQUIRED, 'Path to your database config file', '')
			->addOption('mysql_root_password', null, InputOption::VALUE_REQUIRED, 'Root password', '')
			->addOption('ssd', null, InputOption::VALUE_REQUIRED, 'Size of ssd storage, default 1Gi', '1Gi')
			->addOption('vcpu', null, InputOption::VALUE_REQUIRED, 'The number of virtual cpu cores available, default 0.25', '0.25');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		try {
			$response = $this->httpHelper->getClient()->request(
				'POST',
				self::API_ENDPOINT,
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody($input),
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
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		} catch (InvalidArgumentException $invalidArgumentException) {
			$output->writeln($invalidArgumentException->getMessage());
			exit(1);
		}

	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		if (!empty($input->getOption('my_cnf')) && !file_exists($input->getOption('my_cnf'))) {
			throw  new InvalidArgumentException('Path to mysql config not valid');
		}

		return json_encode([
			'data' => [
				'attributes' => array_merge([
					'description'         => $input->getOption('description'),
					'memory'              => $input->getOption('memory'),
					'my_cnf'              => !(empty($input->getOption('my_cnf'))) ? file_get_contents($input->getOption('my_cnf')) : '',
					'mysql_root_password' => $input->getOption('mysql_root_password'),
					'ssd'                 => $input->getOption('ssd'),
					'vcpu'                => (float)$input->getOption('vcpu'),
				], !empty($input->getOption('organization_id')) ? ['organization_id' => $input->getOption('organization_id')] : []),
				'type'       => 'databases',
			],
		]);
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
		$headers = ['Id'];
		$row = [$serializedDocument['data']['id']];

		foreach ($serializedDocument['data']['attributes'] as $key => $value) {
			if (!empty($value) && !in_array($key, self::EXCLUDE_FROM_OUTPUT)) {
				array_push($headers, $key);
				array_push($row, $value);
			}
		}
		$table->setHeaders($headers);
		$table->addRow($row);
		return $table;
	}
}