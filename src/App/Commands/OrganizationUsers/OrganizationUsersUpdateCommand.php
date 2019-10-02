<?php

namespace Lio\App\Commands\OrganizationUsers;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Lio\App\Console\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class OrganizationUsersUpdateCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/organization_users/%s';

	/**
	 * @var string
	 */
	protected static $defaultName = 'organizations_users:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update an organization/user relationship (Allow to set/remove selected user role as an organization admin)')
			->setHelp('Update an organization/user relationship, api reference' . PHP_EOL . 'https://www.lamp.io/api#/organization_users/organizationUsersUpdate')
			->addArgument('organization_user_id', InputArgument::REQUIRED, 'The ID of the organization_user')
			->addOption('admin', null, InputOption::VALUE_NONE, 'Set selected user as admin of organization (if you need to remove admin role from selected user, just omit this option )');
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
			'Updating organization/user relationship ' . $input->getArgument('organization_user_id'),
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$response = $this->httpHelper->getClient()->request(
				'PATCH',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('organization_user_id')
				),
				[
					'headers'  => $this->httpHelper->getHeaders(),
					'body'     => $this->getRequestBody($input),
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

	protected function getRequestBody(InputInterface $input): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'organization_admin' => $input->getOption('admin'),
				],
				'id'         => $input->getArgument('organization_user_id'),
				'type'       => 'organization_users',
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