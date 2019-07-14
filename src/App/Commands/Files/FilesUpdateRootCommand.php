<?php

namespace Console\App\Commands\Files;

use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateRootCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files';

	protected static $defaultName = 'files:update:root';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('This is only used to set apache_writeable for the root directory of an app')
			->setHelp('https://www.lamp.io/api#/files/filesUpdate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app');
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
				'PATCH',
				sprintf(self::API_ENDPOINT, $input->getArgument('app_id')),
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody(),
				]);
			$output->writeln('<info>`apache_writeable` is set for the root directory of an app</info>');
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		}
	}

	/**
	 * @return string
	 */
	protected function getRequestBody(): string
	{
		return json_encode([
			'data' =>
				[
					'attributes' =>
						[
							'apache_writeable' => true,
						],
					'type'       => 'files',
				],
		]);
	}
}