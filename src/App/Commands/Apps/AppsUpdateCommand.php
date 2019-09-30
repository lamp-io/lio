<?php

namespace Console\App\Commands\Apps;

use Console\App\Commands\Command;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Exception;
use Symfony\Component\Console\Helper\Table;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use GuzzleHttp\Exception\BadResponseException;

class AppsUpdateCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s';

	const HTTPD_CONF_OPTION_NAME = 'httpd_conf';

	const PHP_INI_OPTION_NAME = 'php_ini';

	const EXCLUDE_FROM_OUTPUT = [
		'ssh_pub_key',
	];

	const ALLOW_ZERO_VALUE = [
		'replicas',
	];

	protected static $defaultName = 'apps:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update app')
			->setHelp('Update app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/apps/appsUpdate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addOption('organization_id', null, InputOption::VALUE_REQUIRED, 'The ID of the organization this app belongs to. STRING')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'A description', '')
			->addOption(self::HTTPD_CONF_OPTION_NAME, null, InputOption::VALUE_REQUIRED, 'Path to your httpd.conf', '')
			->addOption('max_replicas', null, InputOption::VALUE_REQUIRED, 'The maximum number of auto-scaled replicas INT', '')
			->addOption('memory', 'm', InputOption::VALUE_REQUIRED, 'The amount of memory available (example: 1Gi) STRING', '')
			->addOption('min_replicas', null, InputOption::VALUE_REQUIRED, 'The minimum number of auto-scaled replicas INT', '')
			->addOption(self::PHP_INI_OPTION_NAME, null, InputOption::VALUE_REQUIRED, 'Path to your php.ini', '')
			->addOption('replicas', 'r', InputOption::VALUE_REQUIRED, 'The number current number replicas available. 0 stops app. INT', '')
			->addOption('vcpu', null, InputOption::VALUE_REQUIRED, 'The number of virtual cpu cores available (maximum: 4, minimum: 0.25) FLOAT', '')
			->addOption('github_webhook_secret', null, InputOption::VALUE_REQUIRED, 'Github web-hook secret token', '')
			->addOption('webhook_run_command', null, InputOption::VALUE_REQUIRED, 'Github web-hook command', '')
			->addOption('hostname', null, InputOption::VALUE_REQUIRED, 'The hostname for the app', '')
			->addOption('hostname_certificate_valid', null, InputOption::VALUE_REQUIRED, 'Is hostname certificate valid')
			->addOption('public', 'p', InputOption::VALUE_REQUIRED, 'Public for read-only')
			->addOption('delete_protection', null, InputOption::VALUE_REQUIRED, 'When enabled the app can not be deleted')
			->setBoolOptions(['delete_protection', 'public', 'hostname_certificate_valid']);
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
			'Updating app',
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
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
					'progress'  => function () use ($progressBar) {
						$progressBar->advance();
					},
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->write(PHP_EOL);
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$table = $this->getOutputAsTable($document, new Table($output));
				$table->render();
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
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
			if ((!empty($attribute)) && !in_array($key, self::EXCLUDE_FROM_OUTPUT)) {
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
	 * @return string
	 * @throws Exception
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		if (!empty($input->getOption(self::HTTPD_CONF_OPTION_NAME)) && !file_exists($input->getOption(self::HTTPD_CONF_OPTION_NAME))) {
			throw new InvalidArgumentException('File ' . $input->getOption(self::HTTPD_CONF_OPTION_NAME) . ' not exist');
		}
		if (!empty($input->getOption(self::PHP_INI_OPTION_NAME)) && !file_exists($input->getOption(self::PHP_INI_OPTION_NAME))) {
			throw new InvalidArgumentException('File ' . $input->getOption(self::PHP_INI_OPTION_NAME) . ' not exist');
		}
		$attributes = [
			'description'                => (string)$input->getOption('description'),
			'httpd_conf'                 => !empty($input->getOption(self::HTTPD_CONF_OPTION_NAME)) ? file_get_contents($input->getOption(self::HTTPD_CONF_OPTION_NAME)) : '',
			'max_replicas'               => (int)$input->getOption('max_replicas'),
			'memory'                     => (string)$input->getOption('memory'),
			'min_replicas'               => (int)$input->getOption('min_replicas'),
			'php_ini'                    => !empty($input->getOption(self::PHP_INI_OPTION_NAME)) ? file_get_contents($input->getOption(self::PHP_INI_OPTION_NAME)) : '',
			'replicas'                   => (int)$input->getOption('replicas'),
			'vcpu'                       => (float)$input->getOption('vcpu'),
			'github_webhook_secret'      => (string)$input->getOption('github_webhook_secret'),
			'webhook_run_command'        => (string)$input->getOption('webhook_run_command'),
			'hostname'                   => (string)$input->getOption('hostname'),
			'hostname_certificate_valid' => $input->getOption('hostname_certificate_valid') == 'true',
			'public'                     => $input->getOption('public') == 'true',
			'delete_protection'          => $input->getOption('delete_protection') == 'true',
		];
		var_export($attributes);
		$attributes = array_filter($attributes, function ($value, $key) use ($input) {
			if (((int)$value === 0 && in_array($key, self::ALLOW_ZERO_VALUE) && !empty($input->getOption($key))) || !empty($input->getOption($key))) {
				return true;
			}
			return false;
		}, ARRAY_FILTER_USE_BOTH);
		var_export($attributes);
		if (empty($attributes)) {
			throw new InvalidArgumentException('Command requires at least one option to be executed.');
		}

		return json_encode([
			'data' => [
				'attributes' => $attributes,
				'id'         => $input->getArgument('app_id'),
				'type'       => 'apps',
			],
		]);
	}
}
