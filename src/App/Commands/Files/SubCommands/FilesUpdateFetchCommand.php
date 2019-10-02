<?php

namespace Lio\App\Commands\Files\SubCommands;

use Lio\App\Console\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateFetchCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/?%s';

	protected static $defaultName = 'files:update:fetch';

	protected $subCommand = [
		'command' => 'fetch',
		'source'  => '',
	];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Fetch file from URL')
			->setHelp('Fetch file from URL, api reference' . PHP_EOL . 'https://www.lamp.io/api#/files/filesUpdateID')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file_id', InputArgument::REQUIRED, 'File ID of file to fetch')
			->addArgument('source', InputArgument::REQUIRED, 'URL to fetch');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		try {
			$this->subCommand['source'] = $input->getArgument('source');
			$progressBar = self::getProgressBar(
				'Fetching it',
				(empty($input->getOption('json'))) ? $output : new NullOutput()
			);
			$response = $this->httpHelper->getClient()->request(
				'POST',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_id'),
					http_build_query($this->subCommand)
				),
				[
					'headers'  => $this->httpHelper->getHeaders(),
					'body'     => $this->getRequestBody(
						$input->getArgument('file_id')
					),
					'progress' => function () use ($progressBar) {
						$progressBar->advance();
					},
				]);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->write(PHP_EOL);
				$output->writeln(PHP_EOL . '<info>Success, file ' . $input->getArgument('file_id') . ' has been filled, with fetched data</info>');
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $remoteFile
	 * @return string
	 */
	protected function getRequestBody(string $remoteFile): string
	{
		return json_encode([
			'data' => [
				'id'   => ltrim($remoteFile, '/'),
				'type' => 'files',
			],
		], JSON_UNESCAPED_SLASHES);

	}

}
