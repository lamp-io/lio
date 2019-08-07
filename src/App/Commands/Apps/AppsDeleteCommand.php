<?php


namespace Console\App\Commands\Apps;

use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Console\App\Commands\Command;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\QuestionHelper;

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
		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion('<info>Are you sure you want to delete app? (y/N)</info>', false);
		if (!$helper->ask($input, $output, $question)) {
			return 0;
		}
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
			return 1;
		}
	}
}
