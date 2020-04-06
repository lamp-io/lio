<?php

namespace Lio\App\Commands\AutodeploysBranch;

use Exception;
use Lio\App\AbstractCommands\AbstractUpdateCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException as CliInvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AutodeploysBranchUpdateCommand extends AbstractUpdateCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/autodeploys_branch/%s';

	protected static $defaultName = 'autodeploysBranch:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update an autodeploys_branch')
			->setHelp('Update a autodeploys_branch, api reference' . PHP_EOL . 'https://www.lamp.io/api#/autodeploys_branch/autoDeploysBranchUpdate')
			->addArgument('autodeploy_branch_id', InputArgument::REQUIRED, 'The ID of the autodeployBranch')
			->addOption('app_id', 'a', InputOption::VALUE_REQUIRED, 'The ID of the app to deploy against')
			->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Branch name')
			->setApiEndpoint(self::API_ENDPOINT);
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
			$input->getArgument('autodeploy_branch_id')
		));
		$this->setSkipAttributes(['updated_at']);
		return parent::execute($input, $output);
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$attributes = [
			'app_id'        => $input->getOption('app_id'),
			'branch' => $input->getOption('branch'),
		];
		if (empty($attributes)) {
			throw new CliInvalidArgumentException('Command requires at least one option to be executed. List of allowed options');
		}
		return json_encode([
			'data' => [
				'attributes' => $attributes,
				'id'         => $input->getArgument('autodeploy_branch_id'),
				'type'       => 'autodeploys_branch',
			],
		]);
	}
}
