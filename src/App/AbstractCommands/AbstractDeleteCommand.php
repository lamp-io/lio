<?php

namespace Lio\App\AbstractCommands;

use GuzzleHttp\Exception\GuzzleException;
use Lio\App\Helpers\CommandsHelper;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDeleteCommand extends AbstractCommand
{

	protected function configure()
	{
		parent::configure();
		$this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirm delete question');
	}

	/**
	 * @param InputInterface $input
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	protected function sendRequest(InputInterface $input): ResponseInterface
	{
		return $this->httpHelper->getClient()->request(
			'DELETE',
			$this->getApiEndpoint(),
			[
				'headers' => $this->httpHelper->getHeaders(),
			]
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (!CommandsHelper::askConfirm(
			'<info>Are you sure you want to delete ? (y/N)</info>',
			$output,
			$input,
			$this->getHelper('question'))
		) {
			return 0;
		}
		return parent::execute($input, $output);
	}
}