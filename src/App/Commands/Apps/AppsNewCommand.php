<?php


namespace Lio\App\Commands\Apps;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppsNewCommand extends AbstractNewCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/apps';

	const HTTPD_CONF_OPTION_NAME = 'httpd_conf';

	const HTTPD_CONF_DEFAULT = '# httpd.conf default';

	const PHP_INI_DEFAULT = '; php.ini default';

	const PHP_INI_OPTION_NAME = 'php_ini';

	protected static $defaultName = 'apps:new';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Creates a new app')
			->setHelp('Create app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/apps/appsCreate')
			->addArgument('organization_id', InputArgument::OPTIONAL, 'The ID of the organization this app belongs to. STRING')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'A description', 'Default')
			->addOption(self::HTTPD_CONF_OPTION_NAME, null, InputOption::VALUE_REQUIRED, 'Path to your httpd.conf', self::HTTPD_CONF_DEFAULT)
			->addOption('max_replicas', null, InputOption::VALUE_REQUIRED, 'The maximum number of auto-scaled replicas INT', 1)
			->addOption('memory', 'm', InputOption::VALUE_REQUIRED, 'The amount of memory available (example: 1Gi) STRING', '128Mi')
			->addOption('min_replicas', null, InputOption::VALUE_REQUIRED, 'The minimum number of auto-scaled replicas INT', 1)
			->addOption(self::PHP_INI_OPTION_NAME, null, InputOption::VALUE_REQUIRED, 'Path to your php.ini', self::PHP_INI_DEFAULT)
			->addOption('replicas', 'r', InputOption::VALUE_REQUIRED, 'The number current number replicas available. 0 stops app. INT', 1)
			->addOption('vcpu', null, InputOption::VALUE_REQUIRED, 'The number of virtual cpu cores available (maximum: 4, minimum: 0.25) FLOAT', 0.25)
			->addOption('github_webhook_secret', null, InputOption::VALUE_REQUIRED, 'Github web-hook secret token', '')
			->addOption('webhook_run_command', null, InputOption::VALUE_REQUIRED, 'Github web-hook command', '')
			->addOption('hostname', null, InputOption::VALUE_REQUIRED, 'The hostname for the app', '')
			->addOption('hostname_certificate_valid', null, InputOption::VALUE_REQUIRED, 'Is hostname certificate valid')
			->addOption('public', 'p', InputOption::VALUE_REQUIRED, 'Public for read-only')
			->addOption('delete_protection', null, InputOption::VALUE_REQUIRED, 'When enabled the app can not be deleted')
			->setBoolOptions(['delete_protection', 'public', 'hostname_certificate_valid'])
			->setApiEndpoint(self::API_ENDPOINT);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$output->writeln('<info>Your new app successfully created, app id: ' . $document->get('data.id') . '</info>');
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$phpConfig = $this->parseConfigFilesOptions(self::PHP_INI_OPTION_NAME, self::PHP_INI_DEFAULT, $input);
		$httpdConfig = $this->parseConfigFilesOptions(self::HTTPD_CONF_OPTION_NAME, self::HTTPD_CONF_DEFAULT, $input);

		return json_encode([
			'data' => [
				'attributes' =>
					array_merge(
						[
							'description'                => (string)$input->getOption('description'),
							'httpd_conf'                 => $httpdConfig,
							'max_replicas'               => (int)$input->getOption('max_replicas'),
							'memory'                     => (string)$input->getOption('memory'),
							'min_replicas'               => (int)$input->getOption('min_replicas'),
							'php_ini'                    => $phpConfig,
							'replicas'                   => (int)$input->getOption('replicas'),
							'vcpu'                       => (float)$input->getOption('vcpu'),
							'github_webhook_secret'      => (string)$input->getOption('github_webhook_secret'),
							'webhook_run_command'        => (string)$input->getOption('webhook_run_command'),
							'hostname'                   => (string)$input->getOption('hostname'),
							'hostname_certificate_valid' => $input->getOption('hostname_certificate_valid') == 'true',
							'public'                     => $input->getOption('public') == 'true',
							'delete_protection'          => $input->getOption('delete_protection') == 'true',
						],
						!empty($input->getArgument('organization_id')) ? ['organization_id' => (string)$input->getArgument('organization_id')] : []
					),
				'type'       => 'apps',
			],
		]);
	}

	/**
	 * @param string $optionName
	 * @param string $defaultValue
	 * @param InputInterface $input
	 * @return string
	 */
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
