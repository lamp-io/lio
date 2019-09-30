<?php

namespace Console\App\Commands\Apps;

use Art4\JsonApiClient\V1\Document;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Console\App\Commands\Command;
use GuzzleHttp\Exception\BadResponseException;

class AppsListCommand extends Command
{
	protected static $defaultName = 'apps:list';

	const API_ENDPOINT = 'https://api.lamp.io/apps';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns the apps for an organization')
			->setHelp('Get list all allowed apps, api reference' . PHP_EOL . 'https://www.lamp.io/api#/apps/appsList');
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
			'Getting apps',
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				self::API_ENDPOINT,
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
		$table->setHeaderTitle('Apps');
		$table->setHeaders(['App id', 'App description']);
		$serializer = new ArraySerializer(['recursive' => true]);
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

		return $table;
	}
}
