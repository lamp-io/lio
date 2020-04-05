<?php

namespace Lio\App\Commands\Autodeploys;

use Lio\App\AbstractCommands\AbstractDeleteCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

class AutodeploysDeleteCommand extends AbstractDeleteCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/autodeploys/%s';

	protected static $defaultName = 'autodeploys:delete';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete an autodeploy')
			->setHelp('Delete autodeploy, api reference' . PHP_EOL . 'https://www.lamp.io/api#/autodeploys/autoDeploysDelete')
			->addArgument('autodeploy_id', InputArgument::REQUIRED, 'The ID of the autodeploy');

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
			$input->getArgument('autodeploy_id')
		));
		return parent::execute($input, $output);
	}


	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		$output->writeln('<info>Autodeploy ' . $input->getArgument('autodeploy_id') . ' has been deleted</info>');
	}

}
