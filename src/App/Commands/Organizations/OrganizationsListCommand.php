<?php


namespace Console\App\Commands\Organizations;


use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrganizationsListCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/organizations';

	/**
	 * @var string
	 */
	protected static $defaultName = 'organizations:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns this user\'s organizations')
			->setHelp('Get an this user\'s organizations, api reference' . PHP_EOL . 'https://www.lamp.io/api#/organizations/organizationsList');
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
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
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
		$table->setHeaderTitle('Organizations');
		$table->setStyle('box');
		$table->setHeaders([
			'Id', 'Attributes',
		]);
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$sortedData = $this->sortData($serializedDocument['data'], 'updated_at');
		$lastElement = end($sortedData);
		foreach ($sortedData as $key => $data) {
			$attributes = [];
			foreach ($data['attributes'] as $attributeKey => $attribute) {
				array_push($attributes, $attributeKey . ' : ' . $attribute);
			}
			$table->addRow([
				$data['id'],
				implode(PHP_EOL, $attributes),
			]);

			if ($lastElement != $data) {
				$table->addRow(new TableSeparator());
			}
		}
		return $table;
	}
}