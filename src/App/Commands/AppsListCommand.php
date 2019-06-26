<?php

namespace Console\App\Commands;

use Art4\JsonApiClient\V1\Document;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;

class AppsListCommand extends Command
{
	protected static $defaultName = 'apps:list';

	const API_ENDPOINT = 'https://api.lamp.io/apps';

	protected function configure()
	{
		$this->setDescription('gets the set of apps from the org associated with your token')
			->setHelp('try rebooting');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				self::API_ENDPOINT,
				['headers' => $this->httpHelper->getHeaders()]
			);
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			die();
		}

		try {
			/** @var Document $document */
			$document = Parser::parseResponseString($response->getBody()->getContents());
			$serializer = new ArraySerializer(['recursive' => true]);
			$table = new Table($output);
			$table->setHeaderTitle('Apps');
			$table->setHeaders(['App name', 'App description']);
			$apps = $serializer->serialize($document);
			foreach ($apps['data'] as $key => $app) {
				$table->addRow([
					$app['id'],
					$app['attributes']['description'],
				]);
				if ($key != count($apps['data']) - 1) {
					$table->addRow(new TableSeparator());
				}
			}
			$table->render();
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
		}

	}
}
