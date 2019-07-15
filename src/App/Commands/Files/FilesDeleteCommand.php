<?php

namespace Console\App\Commands\Files;

use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesDeleteCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s';

	protected static $defaultName = 'files:delete';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Remove file/directory from your app')
			->setHelp('https://www.lamp.io/api#/files/filesDestroy')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('remote_path', InputArgument::REQUIRED, 'Remote path on app, what file/directory you need to delete');
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
			$this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_id'),
					ltrim($input->getArgument('remote_path'), '/')

				),
				[
					'headers' => [
						'Accept'        => 'application/json',
						'Authorization' => $this->httpHelper->getHeader('Authorization'),
					],
				]);
			$output->writeln('<info>Success, ' . $input->getArgument('remote_path') . ' has been deleted</info>');
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		}
	}
}