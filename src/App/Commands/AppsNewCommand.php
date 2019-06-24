<?php


namespace Console\App\Commands;


use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppsNewCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps';

	const HTTPD_CONF_OPTION_NAME = 'httpd_conf';

	const HTTPD_CONF_DEFAULT = '# httpd.conf default';

	const PHP_INI_DEFAULT = '; php.ini default';

	const PHP_INI_OPTION_NAME = 'php_ini';

	protected static $defaultName = 'apps:new';

	protected function configure()
	{
		$this->setDescription('Creates a new app')
			->setHelp('Allow you to create app, api reference https://www.lamp.io/api#/apps/appsCreate')
			->addArgument('organization_id', InputArgument::REQUIRED, 'The ID(uuid) of the organization this app belongs to. STRING')
			->addOption('description', 'desc', InputOption::VALUE_OPTIONAL, 'A description', 'Default')
			->addOption(self::HTTPD_CONF_OPTION_NAME, 'httpd', InputOption::VALUE_OPTIONAL, 'Path to your httpd.conf', self::HTTPD_CONF_DEFAULT)
			->addOption('max_replicas', 'max-repl', InputOption::VALUE_OPTIONAL, 'The maximum number of auto-scaled replicas INT', 1)
			->addOption('memory', 'mem', InputOption::VALUE_OPTIONAL, 'The amount of memory available (example: 1Gi) STRING', '128Mi')
			->addOption('min_replicas', 'min-repl', InputOption::VALUE_OPTIONAL, 'The minimum number of auto-scaled replicas INT', 1)
			->addOption(self::PHP_INI_OPTION_NAME, 'php', InputOption::VALUE_OPTIONAL, 'Path to your php.ini', self::PHP_INI_DEFAULT)
			->addOption('replicas', 'repl', InputOption::VALUE_OPTIONAL, 'The number current number replicas available. 0 stops app. INT', 1)
			->addOption('vcpu', 'vcpu', InputOption::VALUE_OPTIONAL, 'The number of virtual cpu cores available (maximum: 4, minimum: 0.25) FLOAT', 0.25);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$response = $this->httpHelper->getClient()->request(
				'POST',
				self::API_ENDPOINT,
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody($input),
				]
			);
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			die();
		}

		try {
			/** @var Document $document */
			$document = Parser::parseResponseString($response->getBody()->getContents());
			$output->writeln('Your new app successfully created, id: ' . $document->get('data.id'));
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
		}
	}

	protected function getRequestBody(InputInterface $input): string
	{
		$phpConfig = $this->parseConfigFilesOptions(self::PHP_INI_OPTION_NAME, self::PHP_INI_DEFAULT, $input);
		$httpdConfig = $this->parseConfigFilesOptions(self::HTTPD_CONF_OPTION_NAME, self::HTTPD_CONF_DEFAULT, $input);

		return json_encode([
			'data' => [
				'attributes' =>
					[
						'description'     => (string)$input->getOption('description'),
						'httpd_conf'      => $httpdConfig,
						'max_replicas'    => (int)$input->getOption('max_replicas'),
						'memory'          => (string)$input->getOption('memory'),
						'min_replicas'    => (int)$input->getOption('min_replicas'),
						'organization_id' => (string)$input->getArgument('organization_id'),
						'php_ini'         => $phpConfig,
						'replicas'        => (int)$input->getOption('replicas'),
						'vcpu'            => (float)$input->getOption('vcpu'),
					],
				'type'       => 'apps',
			],
		]);
	}

	protected function parseConfigFilesOptions(string $optionName, string $defaultValue, InputInterface $input): string
	{
		if ($input->getOption($optionName) == $defaultValue) {
			$config = $input->getOption($optionName);
		} else {
			if (!file_exists($input->getOption($optionName))) {
				throw new InvalidArgumentException('File ' . $optionName . ' not exist, path: ' . $input->getOption($optionName));
			}
			$config = file_get_contents($input->getOption($optionName));
		}

		return $config;
	}

}