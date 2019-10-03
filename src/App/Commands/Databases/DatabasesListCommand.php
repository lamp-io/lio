<?php


namespace Lio\App\Commands\Databases;


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

class DatabasesListCommand extends AbstractListCommand
{
	protected static $defaultName = 'databases:list';

	const API_ENDPOINT = 'https://api.lamp.io/databases%s';

	const OPTIONS_TO_QUERY_KEYS = [
		'organization_id' => 'filter[organization_id]',
	];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns all databases')
			->setHelp('Get all databases, api reference' . PHP_EOL . 'https://www.lamp.io/api#/databases/databasesList')
			->addOption('organization_id', null, InputOption::VALUE_REQUIRED, 'Filter output by organization id value');
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
			$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_TO_QUERY_KEYS)
		));
		parent::execute($input, $output);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$table = $this->getTableOutput(
			$serializedDocument['data'],
			$document,
			'Databases',
			[
				'Id'          => 'data.%d.id',
				'Description' => 'data.%d.attributes.description',
				'Status'      => 'data.%d.attributes.status',
			],
			new Table($output)
		);
		$table->render();
	}
}