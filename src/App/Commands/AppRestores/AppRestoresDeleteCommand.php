<?php


namespace Console\App\Commands\AppRestores;


use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppRestoresDeleteCommand extends Command
{
	protected static $defaultName = 'app_restores:delete';

	const API_ENDPOINT = 'https://api.lamp.io/app_restores/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete an app restore')
			->setHelp('Delete an app restore, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_restores/appRestoresDelete')
			->addArgument('app_restore_id', InputArgument::REQUIRED, 'The ID of the app restore')
			->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirm delete question');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		if (!$this->askConfirm('<info>Are you sure you want to delete an app restore? (y/N)</info>', $output, $input)) {
			return 0;
		}

		try {
			$response = $this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_restore_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]

			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->writeln(
					'<info>App restore deleted ' . $input->getArgument('app_restore_id') . '</info>'
				);
			}
		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;

		}
	}
}