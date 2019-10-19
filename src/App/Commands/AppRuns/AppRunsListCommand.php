<?php


namespace Lio\App\Commands\AppRuns;

use Lio\App\AbstractCommands\AbstractListCommand;
use Lio\App\Helpers\CommandsHelper;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Helper\Table;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Symfony\Component\Console\Input\InputInterface;

class AppRunsListCommand extends AbstractListCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/app_runs%s';

	const OPTIONS_TO_QUERY_KEYS = [
		'page_number' => 'page[number]',
		'page_size'   => 'page[size]',
	];

	protected static $defaultName = 'app_runs:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return all app runs for all user\'s organizations')
			->setHelp('Get all app runs for all user\'s organizations, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_runs/appRunsList')
			->addOption('page_number', null, InputOption::VALUE_REQUIRED, 'Pagination page', '1')
			->addOption('page_size', null, InputOption::VALUE_REQUIRED, 'Count per paginated page', '100');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_TO_QUERY_KEYS)
		));
		return parent::execute($input, $output);
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
		$sortedData = CommandsHelper::sortData($serializedDocument['data'], 'created_at');
		$table = $this->getTableOutput(
			$sortedData,
			$document,
			'App runs',
			[
				'Id'             => 'data.%d.id',
				'App ID'         => 'data.%d.attributes.app_id',
				'Created at'     => 'data.%d.attributes.created_at',
				'Complete'       => 'data.%d.attributes.complete',
				'CommandWrapper' => 'data.%d.attributes.command',
				'Status'         => 'data.%d.attributes.status',
			],
			new Table($output),
			end($sortedData) ? end($sortedData) : []
		);
		$table->render();
	}

}
