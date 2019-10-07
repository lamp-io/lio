<?php


namespace Lio\App\Commands\Databases;

use Lio\App\AbstractCommands\AbstractDescribeCommand;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabasesDescribeCommand extends AbstractDescribeCommand
{
	protected static $defaultName = 'databases:describe';

	const API_ENDPOINT = 'https://api.lamp.io/databases/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns a database')
			->setHelp('Get database, api reference' . PHP_EOL . 'https://www.lamp.io/api#/databases/databasesShow')
			->addArgument('database_id', InputArgument::REQUIRED, 'The id of database');
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
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('database_id')
		));
		$this->setSkipAttributes([
			'my_cnf',
		]);
		parent::execute($input, $output);
//		$progressBar = self::getProgressBar(
//			'Getting database ' . $input->getArgument('database_id'),
//			(empty($input->getOption('json'))) ? $output : new NullOutput()
//		);
//		try {
//			$response = $this->httpHelper->getClient()->request(
//				'GET',
//				sprintf(
//					self::API_ENDPOINT,
//					$input->getArgument('database_id')
//				),
//				[
//					'headers'  => $this->httpHelper->getHeaders(),
//					'progress' => function () use ($progressBar) {
//						$progressBar->advance();
//					},
//				]
//			);
//			if (!empty($input->getOption('json'))) {
//				$output->writeln($response->getBody()->getContents());
//			} else {
//				$output->write(PHP_EOL);
//				/** @var Document $document */
//				$document = Parser::parseResponseString($response->getBody()->getContents());
//				$table = $this->getOutputAsTable($document, new Table($output));
//				$table->render();
//			}
//		} catch (BadResponseException $badResponseException) {
//			$output->write(PHP_EOL);
//			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
//			return 1;
//		}

	}

//	/**
//	 * @param Document $document
//	 * @param Table $table
//	 * @return Table
//	 */
//	protected function getOutputAsTable(Document $document, Table $table): Table
//	{
//		$table->setHeaderTitle('Database');
//		$serializer = new ArraySerializer(['recursive' => true]);
//		$serializedDocument = $serializer->serialize($document);
//		$header = ['Id', 'Attributes'];
//		$table->setHeaders($header);
//		$sortedData = $this->sortData($serializedDocument['data'], 'updated_at');
//		$row = [$sortedData['id']];
//		$attributeArray = [];
//		foreach ($sortedData['attributes'] as $attributeKey => $attribute) {
//			if ($attributeKey == 'my_cnf' && !empty($attribute)) {
//				$mysqlConfig = $attributeKey . ' : ' . wordwrap(trim(preg_replace(
//						'/\s\s+|\t/', ' ', $attribute
//					)), 40);
//			} else {
//				$attributeArray[] = $attributeKey . ' : ' . $attribute;
//			}
//		}
//		$attributes = implode(PHP_EOL, $attributeArray);
//		$attributes .= !empty($mysqlConfig) ? PHP_EOL . $mysqlConfig : '';
//		unset($mysqlConfig);
//		$row[] = $attributes;
//		$table->addRow($row);
//		return $table;
//
//	}
}

