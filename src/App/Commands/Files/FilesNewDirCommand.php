<?php


namespace Console\App\Commands\Files;


use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesNewDirCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s';

	protected static $defaultName = 'files:new:dir';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Create directory on your app')
			->setHelp('Create directory, api reference' . PHP_EOL . 'https://www.lamp.io/api#/files/filesCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file_id', InputArgument::REQUIRED, 'File ID of directory to create');
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

		$progressBar = self::getProgressBar('Deleting ' . $input->getArgument('file_id'), $output);
		try {
			$this->httpHelper->getClient()->request(
				'POST',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_id'),
					trim($input->getArgument('file_id'), '/')

				),
				[
					'headers'  => [
						'Accept'        => 'application/json',
						'Authorization' => $this->httpHelper->getHeader('Authorization'),
					],
					'body'     => $this->getRequestBody(),
					'progress' => function () use ($progressBar) {
						$progressBar->advance();
					},
				]);
			$progressBar->finish();
			$output->write(PHP_EOL);
			if (empty($input->getOption('json'))) {
				$output->writeln('<info>Success, ' . $input->getArgument('file_id') . ' has been deleted</info>');
			}
		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	protected function getRequestBody(string $fileId): string
	{
		return json_encode([
			'data' => [
				'type'       => 'files',
				'id'         => trim($fileId),
				'attributes' => [
					'contents' => '',
					'is_dir'   => true,
				],
			],
		]);
	}
}