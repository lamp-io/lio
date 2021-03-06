<?php

namespace Lio\App\Commands\OrganizationUsers;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Lio\App\Console\CommandWrapper;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Lio\App\Helpers\CommandsHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class OrganizationUsersDescribeCommandWrapper extends CommandWrapper
{
	const API_ENDPOINT = 'https://api.lamp.io/organization_users/%s';

	/**
	 * @var string
	 */
	protected static $defaultName = 'organizations_users:describe';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns a organization/user relationship')
			->setHelp('Get a organization/user relationship, api reference' . PHP_EOL . 'https://www.lamp.io/api#/organization_users/organizationUsersShow')
			->addArgument('organization_user_id', InputArgument::REQUIRED, 'The ID of the organization_user');
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
		$progressBar = CommandsHelper::getProgressBar(
			'Getting organization/user relationship  ' . $input->getArgument('organization_user_id'),
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('organization_user_id')
				),
				[
					'headers'  => $this->httpHelper->getHeaders(),
					'progress' => function () use ($progressBar) {
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
		$table->setHeaderTitle('Organization User');
		$table->setStyle('box');
		$table->setHeaders([
			'Id', 'Attributes',
		]);
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$attributes = [];
		foreach ($serializedDocument['data']['attributes'] as $attributeKey => $attribute) {
			array_push($attributes, $attributeKey . ' : ' . $attribute);
		}
		$table->addRow([
			$serializedDocument['data']['id'],
			implode(PHP_EOL, $attributes),
		]);
		return $table;
	}
}