<?php

namespace Console\App\Commands;

use Console\App\Helpers\AuthHelper;
use Console\App\Helpers\HttpHelper;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
	protected $httpHelper;

	public function __construct(ClientInterface $httpClient, $name = null)
	{
		parent::__construct($name);
		$this->httpHelper = new HttpHelper($httpClient);
	}

	protected function configure()
	{
		$this->addOption('json', 'j', InputOption::VALUE_NONE, 'Output as a raw json');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \Exception
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (!AuthHelper::isTokenExist()) {
			$this->callAuthCommand();
		}

		$this->httpHelper->setHeader('Authorization', 'Bearer ' . AuthHelper::getToken());
	}

	/**
	 * @throws \Exception
	 */
	protected function callAuthCommand()
	{
		$authCommand = $this->getApplication()->find(AuthCommand::getDefaultName());
		$args = [
			'command' => AuthCommand::getDefaultName(),
		];
		$input = new ArrayInput($args);
		$authCommand->run($input, new ConsoleOutput());
	}
}