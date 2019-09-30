<?php

namespace Lio\App\Commands\DbRestores;

use Lio\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbRestoresDeleteCommand extends Command
{
	protected static $defaultName = 'db_restores:delete';

	const API_ENDPOINT = 'https://api.lamp.io/db_restores/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete a db restore job')
			->setHelp('Db restore job, api reference' . PHP_EOL . 'https://www.lamp.io/api#/db_restores/dbRestoresDelete')
			->addArgument('db_restore_id', InputArgument::REQUIRED, 'The ID of the db restore');
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
				'DELETE',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('db_restore_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->writeln(
					'<info>Db restore job deleted ' . $input->getArgument('db_restore_id') . '</info>'
				);
			}
		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}
}