<?php

namespace Lio\App\Commands\Users;

use Lio\App\AbstractCommands\AbstractListCommand;
use Art4\JsonApiClient\V1\Document;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Lio\App\Helpers\CommandsHelper;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;

class UsersListCommand extends AbstractListCommand
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
		$sortedData = CommandsHelper::sortData($serializedDocument['data'], 'updated_at');
		$table = $this->getTableOutput(
			$sortedData,
			$document,
			'Users',
			[
				'Id'         => 'data.%d.id',
				'Created at' => 'data.%d.attributes.created_at',
				'Email'      => 'data.%d.attributes.email',
			],
			new Table($output),
			end($sortedData) ? end($sortedData) : [],
			80
		);
		$table->render();
	}
}