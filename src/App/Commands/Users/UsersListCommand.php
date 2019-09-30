<?php

namespace Console\App\Commands\Users;

use Console\App\Commands\Command;
use Art4\JsonApiClient\V1\Document;
use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;

class UsersListCommand extends Command
{

	const API_ENDPOINT = 'https://api.lamp.io/users%s';

	const OPTIONS_TO_QUERY_KEYS = [
		'email'           => 'filter[email]',
		'organization_id' => 'filter[organization_id]',
	];

	protected static $defaultName = 'users:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns users')
			->setHelp('https://www.lamp.io/api#/users/usersList')
			->setHelp('Get all users, api reference' . PHP_EOL . 'https://www.lamp.io/api#/users/usersList')
			->addOption('organization_id', 'o', InputOption::VALUE_REQUIRED, 'Comma-separated list of requested organization_ids. If omitted defaults to user\'s default organization')
			->addOption('email', 'e', InputOption::VALUE_REQUIRED, 'Email address to filter for');
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
		$progressBar = self::getProgressBar(
			'Getting users',
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				sprintf(
					self::API_ENDPOINT,
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
		$table->setHeaderTitle('Users');
		$table->setHeaders([
			'User ID', 'Type', 'Attributes',
		]);
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$sortedData = $this->sortData($serializedDocument['data'], 'updated_at');
		$lastElement = end($sortedData);
		foreach ($sortedData as $key => $value) {
			$row = [$value['id'], $value['type']];
			if (!empty($value['attributes'])) {
				$attributes = [];
				foreach ($value['attributes'] as $attributeKey => $attribute) {
					if ($attributeKey == 'organization_users') {
						$attributes[] = 'organization_users: ' . PHP_EOL . $this->parseOrganizationUsers($attribute);

					} else {
						$attributes[] = $attributeKey . ' => ' . $attribute;
					}
				}
				$row[] = (implode(PHP_EOL, $attributes));
				$table->addRow($row);
			}
			if ($lastElement != $value) {
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
				if (!empty($value)) {
					$data[] = '  * ' . $key . ' => ' . $value;
				}
			}
			$parseOrgUsersData[] = implode(PHP_EOL, $data);
		}

		return implode(PHP_EOL, $parseOrgUsersData);
	}
}