<?php


namespace Console\App\Commands;

use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FilesDownloadCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s';

	protected static $defaultName = 'files:download';

	const DEFAULT_FORMAT = 'zip';

	const RESPONSE_FORMAT_TYPES = [
		'gzip' => [
			'AcceptHeader' => 'Accept: application/x-gzip',
		],
		'zip'  => [
			'AcceptHeader' => 'Accept: application/zip',
		],
	];

	protected function configure()
	{
		$this->setDescription('Download files')
			->setHelp('try rebooting')
			->addArgument('app_id', InputArgument::REQUIRED, 'App ID')
			->addArgument('file_id', InputArgument::OPTIONAL, 'The ID of the file. The ID is also the file path relative to its app root. Default value its a root of your app', '/')
			->addOption('gzip', '', InputOption::VALUE_NONE, 'Set this flag, if you want response as a gzip archive');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$format = $this->getResponseFormat($input);
			$fileName = time() . '_' . $input->getArgument('app_id') . '.' . $format;
			$output->writeln('<info>Downloading started</info>');
			$this->httpHelper->getClient()->request(
				'GET',
				sprintf(self::API_ENDPOINT,
					$input->getArgument('app_id'),
					urlencode($input->getArgument('file_id'))
				),
				[
					'headers'  => array_merge(
						$this->httpHelper->getHeaders(),
						['Accept' => self::RESPONSE_FORMAT_TYPES[$format]['AcceptHeader']]
					),
					'sink'     => fopen($this->getPath($fileName), 'w+'),
				]
			);
			$output->writeln(PHP_EOL . '<info>File received, ' . $fileName . '</info>');
		} catch (GuzzleException $exception) {
			$output->writeln($exception->getMessage());
			exit(1);
		}
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getResponseFormat(InputInterface $input): string
	{
		$format = self::DEFAULT_FORMAT;
		foreach ($input->getOptions() as $key => $option) {
			if (array_key_exists($key, self::RESPONSE_FORMAT_TYPES) && !empty($option)) {
				$format = $key;
			}
		}
		return $format;
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getPath(string $fileName): string
	{
		return getenv('HOME') . getenv("HOMEDRIVE") . getenv("HOMEPATH") . DIRECTORY_SEPARATOR . '.config' . DIRECTORY_SEPARATOR . 'lamp.io' . DIRECTORY_SEPARATOR . $fileName;
	}
}