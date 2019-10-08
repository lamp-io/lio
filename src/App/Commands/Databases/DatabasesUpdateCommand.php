<?php


namespace Lio\App\Commands\Databases;

use Lio\App\AbstractCommands\AbstractUpdateCommand;
use Lio\App\Helpers\PasswordHelper;
use InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidArgumentException as CliInvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Exception;

class DatabasesUpdateCommand extends AbstractUpdateCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/databases/%s';

	protected static $defaultName = 'databases:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update a database')
			->setHelp('Update a database, api reference' . PHP_EOL . 'https://www.lamp.io/api#/databases/databasesUpdate')
			->addArgument('database_id', InputArgument::REQUIRED, 'The id of database')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'Description of your database')
			->addOption('memory', 'm', InputOption::VALUE_REQUIRED, 'Amount of virtual memory on your database')
			->addOption('organization_id', null, InputOption::VALUE_REQUIRED, 'Name of your organization')
			->addOption('my_cnf', null, InputOption::VALUE_REQUIRED, 'Path to your database config file')
			->addOption('mysql_root_password', null, InputOption::VALUE_NONE, 'Root password')
			->addOption('ssd', null, InputOption::VALUE_REQUIRED, 'Size of ssd storage')
			->addOption('vcpu', null, InputOption::VALUE_REQUIRED, 'The number of virtual cpu cores available')
			->addOption('delete_protection', null, InputOption::VALUE_REQUIRED, 'When enabled the database can not be deleted')
			->setBoolOptions(['delete_protection']);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('database_id')
		));
		$this->setSkipAttributes([
			'my_cnf',
		]);
		if ($input->getOption('mysql_root_password')) {
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$question = PasswordHelper::getPasswordQuestion(
				'<info>Please provide a password for the MySQL root user</info>',
				null,
				$output
			);
			$password = $helper->ask($input, $output, $question);
			$input->setOption('mysql_root_password', $password);
		}
		parent::execute($input, $output);

	}

	/**
	 * @param InputInterface $input
	 * @return string
	 * @throws Exception
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		if (!empty($input->getOption('my_cnf')) && !file_exists($input->getOption('my_cnf'))) {
			throw  new InvalidArgumentException('Path to mysql config not valid');
		}
		$attributes = [];
		foreach ($input->getOptions() as $key => $val) {
			if (!in_array($key, self::DEFAULT_CLI_OPTIONS) && !empty($val)) {
				if ($key == 'my_cnf') {
					$attributes[$key] = file_get_contents($val);
				} elseif ($key == 'vcpu') {
					$attributes[$key] = (float)$val;
				} elseif ($key == 'delete_protection') {
					$attributes[$key] = $val == 'true';
				} else {
					$attributes[$key] = $val;
				}
			}
		}

		if (empty($attributes)) {
			throw new CliInvalidArgumentException('Command requires at least one option to be executed. List of allowed options');
		}

		return json_encode([
			'data' => [
				'attributes' => $attributes,
				'id'         => $input->getArgument('database_id'),
				'type'       => 'databases',
			],
		]);
	}
}