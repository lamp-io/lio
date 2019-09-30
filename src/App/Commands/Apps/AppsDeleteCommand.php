<?php


namespace Lio\App\Commands\Apps;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Lio\App\Commands\Command;
use GuzzleHttp\Exception\BadResponseException;

class AppsDeleteCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s';

	protected static $defaultName = 'apps:delete';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete an app')
			->setHelp('Delete app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/apps/appsDestroy')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
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

		if (!$this->askConfirm('<info>Are you sure you want to delete app? (y/N)</info>', $output, $input)) {
			return 0;
		}
		$progressBar = self::getProgressBar(
			'Deleting app ' . $input->getArgument('app_id'),
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$response = $this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(self::API_ENDPOINT, $input->getArgument('app_id')),
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
				$output->writeln('Delete Success, for ' . $input->getArgument('app_id'));
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}
}
