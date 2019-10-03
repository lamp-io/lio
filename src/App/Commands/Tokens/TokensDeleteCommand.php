<?php


namespace Lio\App\Commands\Tokens;

use Lio\App\AbstractCommands\AbstractDeleteCommand;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TokensDeleteCommand extends AbstractDeleteCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/tokens/%s';

	/**
	 * @var string
	 */
	protected static $defaultName = 'tokens:delete';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete a token')
			->setHelp('Delete a token, api reference' . PHP_EOL . 'https://www.lamp.io/api#/tokens/tokensDelete')
			->addArgument('token_id', InputArgument::REQUIRED, 'The ID of the token');
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
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('token_id')
		));
		parent::execute($input, $output);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		$output->writeln('<info>Token ' . $input->getArgument('token_id') . ' has been deleted</info>');
	}
}