<?php

namespace Lio\App\Commands\AppRuns;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Lio\App\Console\Command;
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
			->setHelp('Run command on app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_runs/appRunsCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('exec', InputArgument::REQUIRED, 'Command to run');
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
				$appRunId = $document->get('data.id');
				$progressBar = Command::getProgressBar($input->getArgument('exec'), $output);
				$progressBar->start();
				while (!AppRunsDescribeCommand::isExecutionCompleted($appRunId, $this->getApplication())) {
					$progressBar->advance();
				}
				$progressBar->finish();
				$output->write(PHP_EOL);
				$output->write('<info>' . $this->getCommandOutput($appRunId) . '</info>');
			}
		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $appRunId
	 * @return string
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function getCommandOutput(string $appRunId): string
	{
		$appRunsNewCommand = $this->getApplication()->find(AppRunsDescribeCommand::getDefaultName());
		$args = [
			'command'    => AppRunsDescribeCommand::getDefaultName(),
			'app_run_id' => $appRunId,
			'--json'     => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($appRunsNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			$output = $document->get('data.attributes.output');
		}

		return !empty($output) ? $output : '';
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
