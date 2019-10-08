<?php


namespace Lio\App\Commands\Tokens;

use Lio\App\AbstractCommands\AbstractUpdateCommand;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TokensUpdateCommand extends AbstractUpdateCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/tokens/%s';

	/**
	 * @var string
	 */
	protected static $defaultName = 'tokens:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update a token')
			->setHelp('Update a token, api reference' . PHP_EOL . 'https://www.lamp.io/api#/tokens/tokensList')
			->addArgument('token_id', InputArgument::REQUIRED, 'The ID of the token')
			->addOption('enable', null, InputOption::VALUE_NONE, 'Enable token')
			->addOption('disable', null, InputOption::VALUE_NONE, 'Disable token');
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
			$input->getArgument('token_id')
		));
		parent::execute($input, $output);
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
					'enabled' => $input->getOption('enable'),
				],
				'id'         => $input->getArgument('token_id'),
				'type'       => 'tokens',
			],
		]);
	}
}