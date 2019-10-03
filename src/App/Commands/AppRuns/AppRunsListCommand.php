<?php


namespace Lio\App\Commands\AppRuns;

use Lio\App\AbstractCommands\AbstractListCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Helper\Table;
use Art4\JsonApiClient\Serializer\ArraySerializer;

class AppRunsListCommand extends AbstractListCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/app_runs/';

	protected static $defaultName = 'app_runs:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return all app runs for all user\'s organizations')
			->setHelp('Get all app runs for all user\'s organizations, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_runs/appRunsList')
			->setApiEndpoint(self::API_ENDPOINT);
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
		$sortedData = $this->sortData($serializedDocument['data'], 'created_at');
		$table = $this->getTableOutput(
			$sortedData,
			$document,
			'App runs',
			[
				'Id'         => 'data.%d.id',
				'App ID'     => 'data.%d.attributes.app_id',
				'Created at' => 'data.%d.attributes.created_at',
				'Complete'   => 'data.%d.attributes.complete',
				'Command'    => 'data.%d.attributes.command',
				'Status'     => 'data.%d.attributes.status',
			],
			new Table($output),
			end($sortedData)
		);
		$table->render();
	}

}
