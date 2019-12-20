<?php

namespace Lio\App\Commands\DbRestores;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractDescribeCommand;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class DbRestoresDescribeCommand extends AbstractDescribeCommand
{
	protected static $defaultName = 'db_restores:describe';

	const API_ENDPOINT = 'https://api.lamp.io/db_restores/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return a db restore job')
			->setHelp('Get db restore job, api reference' . PHP_EOL . 'https://www.lamp.io/api#/db_backups/dbRestoresShow')
			->addArgument('db_restore_id', InputArgument::REQUIRED, 'The ID of the db restore');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('db_restore_id')
		));
		return parent::execute($input, $output);
	}

	/**
	 * @param string $dbRestoreId
	 * @param Application $application
	 * @return bool
	 * @throws Exception
	 */
	public static function isDbRestoreCompleted(string $dbRestoreId, Application $application): bool
	{
		$dbBackupDescribeCommand = $application->find(self::getDefaultName());
		$args = [
			'command'       => self::getDefaultName(),
			'db_restore_id' => $dbRestoreId,
			'--json'        => true,
		];
		$bufferOutput = new BufferedOutput();
		$dbBackupDescribeCommand->run(new ArrayInput($args), $bufferOutput);
		/** @var Document $document */
		$document = Parser::parseResponseString($bufferOutput->fetch());
		if ($document->get('data.attributes.status') === 'failed') {
			throw new Exception('Database restore job failed');
		}
		return $document->get('data.attributes.complete');
	}
}