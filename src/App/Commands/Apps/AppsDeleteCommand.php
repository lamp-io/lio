<?php


namespace Console\App\Commands\Apps;

use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Console\App\Commands\Command;

class AppsDeleteCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s';

	protected static $defaultName = 'apps:delete';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Delete an app')
			->setHelp('try rebooting')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(self::API_ENDPOINT, $input->getArgument('app_id')),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
			$output->writeln('Delete Success, for ' . $input->getArgument('app_id'));
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		}
	}
}