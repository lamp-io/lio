<?php

namespace Lio\App\Commands\Files\SubCommands;

use Lio\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FilesNewCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s';

	protected static $defaultName = 'files:new';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Create a file on your app')
			->setHelp('Create a file, api reference' . PHP_EOL . 'https://www.lamp.io/api#/files/filesCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file_id', InputArgument::REQUIRED, 'File ID of a file to create')
			->addArgument('contents', InputArgument::OPTIONAL, 'File content', '')
			->addOption('apache_writable', null, InputOption::VALUE_REQUIRED, 'Allow apache to write to the file ID')
			->addOption('source', null, InputOption::VALUE_REQUIRED, 'A URL to that will be retrieved for fetch content')
			->setBoolOptions(['apache_writable']);
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
		$progressBar = self::getProgressBar(
			'Creating a file ' . $input->getArgument('file_id'),
			$output
		);
		try {
			$response = $this->httpHelper->getClient()->request(
				'POST',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_id'),
					!empty($input->getOption('source')) ? '?command=fetch&source=' . $input->getOption('source') : ''
				),
				[
					'headers'  => $this->httpHelper->getHeaders(),
					'body'     => $this->getRequestBody(
						trim($input->getArgument('file_id'), '/'),
						$input->getArgument('contents'),
						!empty($input->getOption('apache_writable')) && $input->getOption('apache_writable') != 'false'
					),
					'progress' => function () use ($progressBar) {
						$progressBar->advance();
					},
				]);
			$progressBar->finish();
			$output->write(PHP_EOL);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->writeln('<info>Success, file ' . $input->getArgument('file_id') . ' has been created</info>');
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $fileId
	 * @param string $content
	 * @param bool $isApacheWritable
	 * @return string
	 */
	protected function getRequestBody(string $fileId, string $content, bool $isApacheWritable): string
	{
		$body = [
			'data' => [
				'type'       => 'files',
				'id'         => $fileId,
				'attributes' => [
					'contents' => $content,
				],
			],
		];
		if (!empty($isApacheWritable)) {
			$body['data']['attributes']['apache_writable'] = true;
		}
		return json_encode($body);
	}
}