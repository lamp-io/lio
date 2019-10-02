<?php


namespace Lio\App\Commands\Tokens;

use Lio\App\Console\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class TokensDeleteCommand extends Command
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
			->addArgument('token_id', InputArgument::REQUIRED, 'The ID of the token.')
			->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirm delete question');
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
		parent::execute($input, $output);
		$progressBar = self::getProgressBar(
			'Deleting token ' . $input->getArgument('token_id'),
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			if (!$this->askConfirm('<info>Are you sure you want to token? (y/N)</info>', $output, $input)) {
				return 0;
			}
			$response = $this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('token_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
					'progress'  => function () use ($progressBar) {
						$progressBar->advance();
					},
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->write(PHP_EOL);
				$output->writeln(
					'<info>Token ' . $input->getArgument('token_id') . ' deleted</info>'
				);
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}
}