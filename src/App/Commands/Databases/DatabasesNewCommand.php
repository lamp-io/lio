<?php

namespace Lio\App\Commands\Databases;

use Lio\App\AbstractCommands\AbstractNewCommand;
use Lio\App\Helpers\PasswordHelper;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Helper\Table;
use Art4\JsonApiClient\Helper\Parser;

class DatabasesNewCommand extends AbstractNewCommand
{
	protected static $defaultName = 'databases:new';

	const API_ENDPOINT = 'https://api.lamp.io/databases';

	/**
	 * @var
	 * string
	 */
	protected $password = '';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Create a new database')
			->setHelp('Create a new database, api reference' . PHP_EOL . 'https://www.lamp.io/api#/databases/databasesCreate')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'Description of your database', '')
			->addOption('memory', 'm', InputOption::VALUE_REQUIRED, 'Amount of virtual memory on your database, default 512Mi', '512Mi')
			->addOption('organization_id', null, InputOption::VALUE_REQUIRED, 'Name of your organization', '')
			->addOption('mysql_root_password', null, InputOption::VALUE_OPTIONAL, 'Root password', false)
			->addOption('my_cnf', null, InputOption::VALUE_REQUIRED, 'Path to your database config file', '')
			->addOption('ssd', null, InputOption::VALUE_REQUIRED, 'Size of ssd storage, default 1Gi', '1Gi')
			->addOption('vcpu', null, InputOption::VALUE_REQUIRED, 'The number of virtual cpu cores available, default 0.25', '0.25')
			->addOption('delete_protection', null, InputOption::VALUE_REQUIRED, 'When enabled the database can not be deleted')
			->setBoolOptions(['delete_protection'])
			->setApiEndpoint(self::API_ENDPOINT);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($this->isPassWordOptionExist($input)) {
			$this->password = $this->handlePasswordOption($input, $output);
		}
		parent::execute($input, $output);
	}

	protected function renderOutput(ResponseInterface $response, OutputInterface $output)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$table = $this->getTableOutput(
			$document,
			'Database',
			[
				'Id'                => 'data.id',
				'Status'            => 'data.attributes.status',
				'Description'       => 'data.attributes.description',
				'Delete Protection' => 'data.attributes.delete_protection',
				'Created at'        => 'data.attributes.created_at',
			],
			new Table($output)
		);
		$table->render();
		if (empty($this->password)) {
			$password = $document->get('data.attributes.mysql_root_password');
			$output->writeln(
				'<warning>Database password: ' . $password . '</warning>' . PHP_EOL . '<warning>WARNING: This is the last opportunity to see this password!</warning>'
			);
		}
	}


	/**
	 * @param InputInterface $input
	 * @return bool
	 */
	protected function isPassWordOptionExist(InputInterface $input): bool
	{
		return $input->getOption('mysql_root_password') !== false;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return string
	 */
	protected function handlePasswordOption(InputInterface $input, OutputInterface $output): string
	{
		if (is_string($input->getOption('mysql_root_password'))) {
			if (empty(trim($input->getOption('mysql_root_password')))) {
				throw new InvalidArgumentException('Error: Refusing to set empty password');
			}
			$password = $input->getOption('mysql_root_password');
		} else {
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$question = PasswordHelper::getPasswordQuestion(
				'<info>Please provide a password for the MySQL root user</info>',
				null,
				$output
			);
			$password = $helper->ask($input, $output, $question);
		}

		return $password;
	}


	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		if (!empty($input->getOption('my_cnf')) && !file_exists($input->getOption('my_cnf'))) {
			throw  new InvalidArgumentException('Path to mysql config not valid');
		}

		$attributes = [];
		foreach ($input->getOptions() as $optionKey => $option) {
			if (!in_array($optionKey, self::DEFAULT_CLI_OPTIONS) && !empty($option)) {
				if ($optionKey == 'delete_protection') {
					$attributes[$optionKey] = $option == 'true';
				} elseif ($optionKey == 'vcpu') {
					$attributes[$optionKey] = (float)$option;
				} else {
					$attributes[$optionKey] = $option;
				}
			}
		}

		if (!empty($this->password)) {
			$attributes['mysql_root_password'] = $this->password;
		}

		return json_encode(
			[
				'data' => [
					'attributes' => $attributes,
					'type'       => 'databases',
				],
			]
		);
	}
}