<?php


namespace Console\App\Commands\Databases;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabasesUpdateCommand extends Command
{
	protected static $defaultName = 'databases:update';

	const API_ENDPOINT = 'https://api.lamp.io/databases/%s';

	const EXCLUDE_FROM_OUTPUT = [
		'my_cnf',
	];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update a database')
			->setHelp('https://www.lamp.io/api#/databases/databasesUpdate')
			->addArgument('database_id', InputArgument::REQUIRED, 'The id of database')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'Description of your database')
			->addOption('memory', 'm', InputOption::VALUE_REQUIRED, 'Amount of virtual memory on your database')
			->addOption('organization_id', null, InputOption::VALUE_REQUIRED, 'Name of your organization')
			->addOption('my_cnf', null, InputOption::VALUE_REQUIRED, 'Path to your database config file')
			->addOption('mysql_root_password', null, InputOption::VALUE_REQUIRED, 'Root password')
			->addOption('ssd', null, InputOption::VALUE_REQUIRED, 'Size of ssd storage')
			->addOption('vcpu', null, InputOption::VALUE_REQUIRED, 'The number of virtual cpu cores available');
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
				'PATCH',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('database_id')

				),
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody($input, $output),
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
	 * @param OutputInterface $output
	 * @return string
	 * @throws \Exception
	 */
	protected function getRequestBody(InputInterface $input, OutputInterface $output): string
	{
		if (!empty($input->getOption('my_cnf')) && !file_exists($input->getOption('my_cnf'))) {
			throw  new InvalidArgumentException('Path to mysql config not valid');
		}
		$attributes = [];
		foreach ($input->getOptions() as $key => $val) {
			if (!in_array($key, self::DEFAULT_CLI_OPTIONS) && !empty($val)) {
				if ($key == 'my_cnf') {
					$attributes[$key] = file_get_contents($val);
				} else {
					$attributes[$key] = $val;
				}
			}
		}

		if (empty($attributes)) {
			$output->writeln('<error>Command requires at least one option to be executed</error>');
			exit(1);
		}

		return json_encode([
			'data' => [
				'attributes' => $attributes,
				'id'         => $input->getArgument('database_id'),
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