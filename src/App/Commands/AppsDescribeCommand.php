<?php

namespace Console\App\Commands;

use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use GuzzleHttp\Exception\GuzzleException;

class AppsDescribeCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/{app_name}';

	/**
	 * @var string
	 */
	protected static $defaultName = 'apps:describe';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('gets the apps you specify')
			->setHelp('try rebooting')
			->addArgument('app_name', InputArgument::REQUIRED, 'which app would you like to describe?');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				str_replace('{app_name}', $input->getArgument('app_name'), self::API_ENDPOINT),
				['headers' => $this->httpHelper->getHeaders()]
			);
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			die();
		}

		try {
			/** @var Document $document */
			$document = Parser::parseResponseString($response->getBody()->getContents());
			$table = new Table($output);
			$table->setHeaderTitle('App Describe');
			$table->setHeaders([
				'Name', 'Description', 'Status', 'VCPU', 'Memory', 'Replicas',
			]);
			$table->addRow([
				$document->get('data.id'),
				$document->get('data.attributes.description'),
				$document->get('data.attributes.status'),
				$document->get('data.attributes.vcpu'),
				$document->get('data.attributes.memory'),
				$document->get('data.attributes.replicas'),
			]);
			$table->render();
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
		}
	}
}

