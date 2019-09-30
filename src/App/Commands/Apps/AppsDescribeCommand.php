<?php

namespace Console\App\Commands\Apps;

use Art4\JsonApiClient\V1\Document;
use Exception;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Art4\JsonApiClient\Helper\Parser;
use GuzzleHttp\Exception\GuzzleException;
use Console\App\Commands\Command;
use Symfony\Component\Console\Output\NullOutput;

class AppsDescribeCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/{app_id}';

	/**
	 * @var string
	 */
	protected static $defaultName = 'apps:describe';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return your app')
			->setHelp('Get selected app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/apps/appsShow')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		$progressBar = self::getProgressBar(
			'Getting app ' . $input->getArgument('app_id'),
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				str_replace('{app_id}', $input->getArgument('app_id'), self::API_ENDPOINT),
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
		$table->setHeaderTitle('App Describe');
		$table->setHeaders([
			'Name', 'Hostname', 'Description', 'Status', 'VCPU', 'Memory', 'Replicas', 'Certificate valid', 'Public',
		]);
		$hostNameCert = $document->has('data.attributes.hostname_certificate_valid') && $document->get('data.attributes.hostname_certificate_valid') ? 'true' : 'false';
		$table->addRow([
			$document->get('data.id'),
			$document->get('data.attributes.hostname'),
			$document->get('data.attributes.description'),
			$document->get('data.attributes.status'),
			$document->get('data.attributes.vcpu'),
			$document->get('data.attributes.memory'),
			$document->get('data.attributes.replicas'),
			$hostNameCert,
			$document->get('data.attributes.public') ? 'true' : 'false',
			$document->get('data.attributes.delete_protection') ? 'true' : 'false',
		]);

		return $table;
	}
}
