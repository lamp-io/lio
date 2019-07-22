<?php

namespace Console\App\Commands\Apps;

use Console\App\Commands\Command;
use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppsUpdateCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s';

	const HTTPD_CONF_OPTION_NAME = 'httpd_conf';

	const PHP_INI_OPTION_NAME = 'php_ini';

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
			->addArgument('organization_id', InputArgument::OPTIONAL, 'The ID(uuid) of the organization this app belongs to. STRING')
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
					'body'    => $this->getRequestBody($input),
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
			/** @var Document $document */
			$document = Parser::parseResponseString($response->getBody()->getContents());
			$output->writeln('Your app ' . $document->get('data.id') .  ' successfully updated');
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
			exit(1);
		}
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$attributes = [];
		foreach ($input->getOptions() as $optionKey => $optionValue) {
			if (!in_array($optionKey, self::DEFAULT_CLI_OPTIONS) && !empty($input->getOption($optionKey))) {
				if ($optionKey == self::HTTPD_CONF_OPTION_NAME || $optionKey == self::PHP_INI_OPTION_NAME) {
					$attributes[$optionKey] = $this->parseConfigFilesOptions($optionKey, $input);
				} else {
					$attributes[$optionKey] = $optionValue;
				}
			}
		}

		$attributes = array_merge($attributes,
			!empty($input->getArgument('organization_id')) ? ['organization_id' => (string)$input->getArgument('organization_id')] : []
		);

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
	 * @return string
	 */
	protected function parseConfigFilesOptions(string $optionName, InputInterface $input): string
	{
		if (!file_exists($input->getOption($optionName))) {
			throw new InvalidArgumentException('File ' . $optionName . ' not exist, path: ' . $input->getOption($optionName));
		}
		$config = file_get_contents($input->getOption($optionName));

		return $config;
	}
}