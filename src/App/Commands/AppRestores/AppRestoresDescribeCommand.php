<?php


namespace Lio\App\Commands\AppRestores;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractDescribeCommand;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class AppRestoresDescribeCommand extends AbstractDescribeCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/app_restores/%s';

	protected static $defaultName = 'app_restores:describe';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return an app restore')
			->setHelp('Get an app restore, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_restores/appRestoresShow')
			->addArgument('app_restore_id', InputArgument::REQUIRED, 'The ID of the app restore');
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
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('app_restore_id')
		));
		return parent::execute($input, $output);
	}

	/**
	 * @param string $appRestoreId
	 * @param Application $application
	 * @return bool
	 * @throws Exception
	 */
	public static function isAppRestoreCompleted(string $appRestoreId, Application $application): bool
	{
		$appRunsDescribeCommand = $application->find(self::getDefaultName());
		$args = [
			'command'        => self::getDefaultName(),
			'app_restore_id' => $appRestoreId,
			'--json'         => true,
		];
		$bufferOutput = new BufferedOutput();
		$appRunsDescribeCommand->run(new ArrayInput($args), $bufferOutput);
		$commandResponse = $bufferOutput->fetch();
		/** @var Document $document */
		$document = Parser::parseResponseString($commandResponse);
		if (!$document->has('data.attributes.status') || $document->get('data.attributes.status') === 'failed') {
			throw new Exception('App restore job failed ' . PHP_EOL . $commandResponse);
		}
		return $document->get('data.attributes.complete');
	}
}