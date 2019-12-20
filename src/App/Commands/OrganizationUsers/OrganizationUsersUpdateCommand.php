<?php

namespace Lio\App\Commands\OrganizationUsers;

use Lio\App\AbstractCommands\AbstractUpdateCommand;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrganizationUsersUpdateCommand extends AbstractUpdateCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/organization_users/%s';

	/**
	 * @var string
	 */
	protected static $defaultName = 'organizations_users:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update an organization/user relationship (Allow to set/remove selected user role as an organization admin)')
			->setHelp('Update an organization/user relationship, api reference' . PHP_EOL . 'https://www.lamp.io/api#/organization_users/organizationUsersUpdate')
			->addArgument('organization_user_id', InputArgument::REQUIRED, 'The ID of the organization_user')
			->addOption('admin', null, InputOption::VALUE_NONE, 'Set selected user as admin of organization (if you need to remove admin role from selected user, just omit this option )');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('organization_user_id')
		));
		return parent::execute($input, $output);
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'organization_admin' => $input->getOption('admin'),
				],
				'id'         => $input->getArgument('organization_user_id'),
				'type'       => 'organization_users',
			],
		]);
	}
}