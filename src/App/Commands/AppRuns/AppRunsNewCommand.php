<?php

namespace Console\App\Commands\AppRuns;

use Art4\JsonApiClient\Exception\ValidationException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Console\App\Commands\Command;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;


class AppRunsNewCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/app_runs/';

	protected static $defaultName = 'app_runs:new';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Run command on app')
			->setHelp('https://www.lamp.io/api#/app_runs/appRunsCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('exec', InputArgument::REQUIRED, 'Command that will be ran');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$response = $this->httpHelper->getClient()->request(
				'POST',
				self::API_ENDPOINT,
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody(
						$input->getArgument('app_id'),
						$input->getArgument('exec')
					),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$output->writeln('Success, your command start run, command id: ' . $document->get('data.id'));
			}
		} catch (ValidationException $validationException) {
			$output->writeln($validationException->getMessage());
			return 1;
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			return 1;
		}
	}

	/**
	 * @param string $appId
	 * @param string $command
	 * @return string
	 */
	protected function getRequestBody(string $appId, string $command): string
	{
		return json_encode([
			'data' =>
				[
					'attributes' =>
						[
							'app_id'  => $appId,
							'command' => $command,
						],
					'type'       => 'app_runs',
				],
		]);
	}
}