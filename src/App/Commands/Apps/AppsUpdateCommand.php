<?php

namespace Console\App\Commands\Apps;

use Console\App\Commands\Command;
use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Helper\Table;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Serializer\ArraySerializer;

class AppsUpdateCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s';

	const HTTPD_CONF_OPTION_NAME = 'httpd_conf';

	const PHP_INI_OPTION_NAME = 'php_ini';

	const EXCLUDE_FROM_OUTPUT = [
		'ssh_pub_key',
	];

	protected static $defaultName = 'apps:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Creates a new app')
			->setHelp('Allow you to create app, api reference https://www.lamp.io/api#/apps/appsCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addOption('organization_id', null, InputOption::VALUE_REQUIRED, 'The ID(uuid) of the organization this app belongs to. STRING')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'A description', '')
			->addOption(self::HTTPD_CONF_OPTION_NAME, null, InputOption::VALUE_REQUIRED, 'Path to your httpd.conf', '')
			->addOption('max_replicas', null, InputOption::VALUE_REQUIRED, 'The maximum number of auto-scaled replicas INT', '')
			->addOption('memory', 'm', InputOption::VALUE_REQUIRED, 'The amount of memory available (example: 1Gi) STRING', '')
			->addOption('min_replicas', null, InputOption::VALUE_REQUIRED, 'The minimum number of auto-scaled replicas INT', '')
			->addOption(self::PHP_INI_OPTION_NAME, null, InputOption::VALUE_REQUIRED, 'Path to your php.ini', '')
			->addOption('replicas', 'r', InputOption::VALUE_REQUIRED, 'The number current number replicas available. 0 stops app. INT', '')
			->addOption('vcpu', null, InputOption::VALUE_REQUIRED, 'The number of virtual cpu cores available (maximum: 4, minimum: 0.25) FLOAT', '')
			->addOption('github_webhook_secret', null, InputOption::VALUE_REQUIRED, 'Github web-hook secret token', '')
			->addOption('webhook_run_command', null, InputOption::VALUE_REQUIRED, 'Github web-hook command', '');
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
			$response = $this->httpHelper->getClient()->request(
				'PATCH',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody($input, $output),
				]
			);
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		} catch (\InvalidArgumentException $invalidArgumentException) {
			$output->writeln($invalidArgumentException->getMessage());
			exit(1);
		}

		try {
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$table = $this->getOutputAsTable($document, new Table($output));
				$table->render();
			}
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
			exit(1);
		}
	}

	/**
	 * @param Document $document
	 * @param Table $table
	 * @return Table
	 */
	protected function getOutputAsTable(Document $document, Table $table): Table
	{
		$table->setHeaderTitle('App');
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$headers = ['Id', 'Attributes'];
		$row = [$serializedDocument['data']['id']];
		$attributes = [];
		foreach ($serializedDocument['data']['attributes'] as $key => $attribute) {
			if (!empty($attribute) && !in_array($key, self::EXCLUDE_FROM_OUTPUT)) {
				$attributes[] = $key . ' : ' . trim(preg_replace(
						'/\s\s+|\t/', ' ', wordwrap($attribute, 40)
					));
			}
		}
		$row[] = implode(PHP_EOL, $attributes);
		$table->setHeaders($headers);
		$table->addRow($row);
		return $table;
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input, OutputInterface $output): string
	{
		$attributes = [];
		foreach ($input->getOptions() as $key => $option) {
			if (!in_array($key, self::DEFAULT_CLI_OPTIONS) && !empty($option)) {
				if ($key == self::HTTPD_CONF_OPTION_NAME || $key == self::PHP_INI_OPTION_NAME) {
					$this->validateConfigFilesOptions($key, $input);
					$attributes[$key] = file_get_contents($option);
				} else {
					$attributes[$key] = $option;
				}
			}
		}
		if (empty($attributes)) {
			$commandOptions = array_filter($input->getOptions(), function ($key) {
				if (!in_array($key, self::DEFAULT_CLI_OPTIONS)) {
					return '--' . $key;
				}
			}, ARRAY_FILTER_USE_KEY);
			$output->writeln('<comment>Command requires at least one option to be executed. List of allowed options:' . implode(PHP_EOL, array_keys($commandOptions)) . '</comment>');
			exit(1);
		}

		return json_encode([
			'data' => [
				'attributes' => $attributes,
				'id'         => $input->getArgument('app_id'),
				'type'       => 'apps',
			],
		]);
	}

	/**
	 * @param string $optionName
	 * @param InputInterface $input
	 */
	protected function validateConfigFilesOptions(string $optionName, InputInterface $input)
	{
		if (!file_exists($input->getOption($optionName))) {
			throw new InvalidArgumentException('File ' . $optionName . ' not exist, path: ' . $input->getOption($optionName));
		}
	}
}