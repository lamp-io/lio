<?php

namespace Lio\App\Commands\AppRuns;

use Exception;
use Lio\App\AbstractCommands\AbstractDescribeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;

class AppRunsDescribeCommand extends AbstractDescribeCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/app_runs/%s';

	protected static $defaultName = 'app_runs:describe';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return app run')
			->setHelp('Get app run, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_runs/appRunsShow')
			->addArgument('app_run_id', InputArgument::REQUIRED, 'ID of app run');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('app_run_id')
		));
		return parent::execute($input, $output);
	}

	/**
	 * @param string $appRunId
	 * @param Application $application
	 * @return bool
	 * @throws Exception
	 */
	public static function isExecutionCompleted(string $appRunId, Application $application): bool
	{
		$appRunsDescribeCommand = $application->find(self::getDefaultName());
		$args = [
			'command'    => self::getDefaultName(),
			'app_run_id' => $appRunId,
			'--json'     => true,
		];
		$bufferOutput = new BufferedOutput();
		$appRunsDescribeCommand->run(new ArrayInput($args), $bufferOutput);
		/** @var Document $document */
		$document = Parser::parseResponseString($bufferOutput->fetch());
		if ($document->get('data.attributes.status') === 'failed') {
			throw new Exception($document->get('data.attributes.output'));
		}
		return $document->get('data.attributes.complete');
	}
}
