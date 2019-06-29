<?php


namespace Console\App\Commands;


use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FilesListCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s';

	const RESPONSE_FORMAT_TYPES = [
		'json' => [
			'AcceptHeader' => 'Accept: application/vnd.api+json',
		],
		'gzip' => [
			'AcceptHeader' => 'Accept: application/x-gzip',
		],
		'zip'  => [
			'AcceptHeader' => 'Accept: application/zip',
		],
	];

	protected static $defaultName = 'files:list';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Return files from the root of an app')
			->setHelp('try rebooting')
			->addArgument('app_id', InputArgument::REQUIRED, 'From which app_id need to get fields?')
			->addArgument('file_id', InputArgument::OPTIONAL, 'The ID of the file. The ID is also the file path relative to its app root.', '/')
			->addOption('gzip', 'g', InputOption::VALUE_NONE, 'Set this flag, if you want response as a gzip archive')
			->addOption('zip', 'z', InputOption::VALUE_NONE, 'Set this flag, if you want response as a zip archive.');
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
		$format = $this->getResponseFormat($input);
		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				sprintf(self::API_ENDPOINT,
					$input->getArgument('app_id'),
					urlencode($input->getArgument('file_id'))
				),
				$this->getRequestOptions($format)
			);
			$output->writeln('<info>File received, ' . $this->getPath('lamp') . '.' . $format . '</info>');
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
		}

	}

	protected function getRequestOptions(string $format)
	{
		return array_merge([
			'headers' => array_merge($this->httpHelper->getHeaders(), ['Accept' => self::RESPONSE_FORMAT_TYPES[$format]['AcceptHeader']]),
		], $format != 'json' ? ['sink' => fopen($this->getPath('lamp') . '.' . $format, 'w')] : []);
	}


	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getPath(string $fileName): string
	{
		return getenv('HOME') . getenv("HOMEDRIVE") . getenv("HOMEPATH") . DIRECTORY_SEPARATOR . '.config' . DIRECTORY_SEPARATOR . 'lamp.io/' . $fileName;
	}


	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getResponseFormat(InputInterface $input): string
	{
		$format = 'json';
		foreach ($input->getOptions() as $key => $option) {
			if (array_key_exists($key, self::RESPONSE_FORMAT_TYPES) && !empty($option)) {
				$format = $key;
			}
		}
		return $format;
	}


}