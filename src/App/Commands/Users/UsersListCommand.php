<?php

namespace Console\App\Commands\Users;

use Console\App\Commands\Command;
use Art4\JsonApiClient\V1\Document;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;

class UsersListCommand extends Command
{

	const API_ENDPOINT = 'https://api.lamp.io/users%s';

	const QUERY_OPTIONS_KEYS = ['filter[email]', 'filter[organization_id]'];

	protected static $defaultName = 'users:list';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('gets the set of apps from the org associated with your token')
			->setHelp('try rebooting')
			->addOption('filter[organization_id]', 'o', InputOption::VALUE_REQUIRED, 'Comma-separated list of requested organization_ids. If omitted defaults to user\'s default organization')
			->addOption('filter[email]', 'e', InputOption::VALUE_REQUIRED, 'Email address to filter for')
			->addOption('json', 'j', InputOption::VALUE_NONE, 'Output as raw json');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				sprintf(
					self::API_ENDPOINT,
					$this->httpHelper->optionsToQuery($input->getOptions(), self::QUERY_OPTIONS_KEYS)
				),
				['headers' => $this->httpHelper->getHeaders()]
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
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
			exit(1);
		}
	}

	/**
	 * @param Document $document
	 * @param Table $table
	 * @return Table
	 */
	protected function getOutputAsTable(Document $document, Table $table): Table
	{
		$table->setHeaderTitle('Users');
		$table->setHeaders([
			'User ID', 'Type', 'Attributes',
		]);
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		foreach ($serializedDocument['data'] as $key => $value) {
			$row = [$value['id'], $value['type']];
			if (!empty($value['attributes'])) {
				$attributes = [];
				foreach ($value['attributes'] as $attributeKey => $attribute) {
					if ($attributeKey == 'organization_users') {
						$attributes[] = 'organization_users: ' . PHP_EOL . $this->parseOrganizationUsers($attribute);

					} else {
						$attributes[] = $attributeKey . ' : ' . $attribute;
					}
				}
				$row[] = (implode(PHP_EOL, $attributes));
				$table->addRow($row);
			}
			if ($key != count($serializedDocument['data']) - 1) {
				$table->addRow(new TableSeparator());
			}
		}
		return $table;
	}

	/**
	 * @param array $organizationUsers
	 * @return string
	 */
	protected function parseOrganizationUsers(array $organizationUsers): string
	{
		$parseOrgUsersData = [];
		foreach ($organizationUsers as $organizationUser) {
			$data = [];
			foreach ((array)$organizationUser as $key => $value) {
				$data[] = '   ' . $key . ' : ' . $value;
			}
			$parseOrgUsersData[] = implode(PHP_EOL, $data);
		}

		return implode(PHP_EOL, $parseOrgUsersData);
	}
}