<?php

namespace Lio\App\Console;

use Lio\App\Helpers\AuthHelper;
use Lio\App\Helpers\HttpHelper;
use Exception;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandWrapper extends Command
{
	protected $httpHelper;

	private $skipAuth;

	/**
	 * CommandWrapper constructor.
	 * @param ClientInterface $httpClient
	 * @param null $name
	 * @param bool $skipAuth
	 */
	public function __construct(ClientInterface $httpClient, $name = null, bool $skipAuth = false)
	{
		parent::__construct($name);
		$this->httpHelper = new HttpHelper($httpClient);
		$this->skipAuth = $skipAuth;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->getFormatter()->setStyle('warning', new OutputFormatterStyle('black', 'yellow'));
		if (empty(AuthHelper::getToken()) && !$this->skipAuth) {
			throw new Exception(
				'Missed auth token' . PHP_EOL . 'Tokens can be generated at https://www.lamp.io/tokens'
			);
		}
		$this->httpHelper->setHeader('Authorization', 'Bearer ' . AuthHelper::getToken());
	}
}