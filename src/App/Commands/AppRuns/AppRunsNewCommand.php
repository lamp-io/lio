<?php

namespace Lio\App\Commands\AppRuns;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Lio\App\Console\Command;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;

class AppRunsNewCommand extends AbstractNewCommand
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
			->addArgument('exec', InputArgument::REQUIRED, 'Command to run')
			->setApiEndpoint(self::API_ENDPOINT);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$appRunId = $document->get('data.id');
		$progressBar = Command::getProgressBar($document->get('data.attributes.command'), $output);
		$progressBar->start();
		while (!AppRunsDescribeCommand::isExecutionCompleted($appRunId, $this->getApplication())) {
			$progressBar->advance();
		}
		$progressBar->finish();
		$output->write(PHP_EOL);
		$output->writeln('<info>' . $this->getCommandOutput($appRunId) . '</info>');
	}


	/**
	 * @param string $appRunId
	 * @return string
	 * @throws Exception
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
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'app_id'  => $input->getArgument('app_id'),
					'command' => $input->getArgument('exec'),
				],
				'type'       => 'app_runs',
			],
		]);
	}
}
