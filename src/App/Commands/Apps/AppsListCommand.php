<?php

namespace Lio\App\Commands\Apps;


use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractListCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Symfony\Component\Console\Input\InputInterface;

class AppsListCommand extends AbstractListCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/apps';

	protected static $defaultName = 'apps:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns the apps for an organization')
			->setHelp('Get list all allowed apps, api reference' . PHP_EOL . 'https://www.lamp.io/api#/apps/appsList')
			->setApiEndpoint(self::API_ENDPOINT);
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
		$table = $this->getTableOutput(
			$serializedDocument['data'],
			$document,
			'Apps',
			[
				'Id'          => 'data.%d.id',
				'Description' => 'data.%d.attributes.description',
				'Status'      => 'data.%d.attributes.status',
				'Public'      => 'data.%d.attributes.public',
			],
			new Table($output)
		);
		$table->render();
	}
}
