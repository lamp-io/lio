<?php

namespace Lio\App\Commands\OrganizationUsers;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractListCommand;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrganizationUsersListCommand extends AbstractListCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/organization_users%s';

	const OPTIONS_TO_QUERY_KEYS = [
		'organization_id' => 'filter[organization_id]',
	];

	/**
	 * @var string
	 */
	protected static $defaultName = 'organizations_users:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns organization/user relationships')
			->setHelp('Get organization/user relationship, api reference' . PHP_EOL . 'https://www.lamp.io/api#/organization_users/organizationUsersList')
			->addOption('organization_id', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of requested organization_ids. If omitted defaults to user\'s default organization');
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
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_TO_QUERY_KEYS)
		));
		parent::execute($input, $output);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$sortedData = $this->sortData($serializedDocument['data'], 'updated_at');
		$table = $this->getTableOutput(
			$sortedData,
			$document,
			'Organization Users',
			[
				'Id'              => 'data.%d.id',
				'User Id'         => 'data.%d.attributes.user_id',
				'Organization Id' => 'data.%d.attributes.organization_id',
				'Admin'           => 'data.%d.attributes.organization_admin',
				'Updated at'      => 'data.%d.attributes.updated_at',
			],
			new Table($output),
			end($sortedData)
		);
		$table->render();
	}
}