<?php

namespace Lio\App\Commands\Apps\SubCommands;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\Commands\Apps\AppsUpdateCommand;
use Lio\App\Commands\Command;
use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class AppUpdatesStatusCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/app_runs/';

	protected static $defaultName = 'apps:update:status';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Enable/disable app')
			->setHelp('Enable/disable app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/apps/appsUpdate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addOption('enable', null, InputOption::VALUE_REQUIRED, 'Enable/disable your stopped app')
			->setBoolOptions(['enable']);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		if (empty($input->getOption('enable'))) {
			$output->writeln('<error>You need to specify --enable true/false to call this command</error>');
			return 1;
		}
		$replicas = $input->getOption('enable') === 'false' ? 0 : 1;

		$appsUpdateCommand = $this->getApplication()->find(AppsUpdateCommand::getDefaultName());
		$args = [
			'command'    => AppsUpdateCommand::getDefaultName(),
			'app_id'     => $input->getArgument('app_id'),
			'--replicas' => $replicas,
			'--json'     => true,
		];
		$bufferedOutput = new BufferedOutput();
		$commandResult = $appsUpdateCommand->run(new ArrayInput($args), $bufferedOutput);
		if ($commandResult === 0) {
			if (!empty($input->getOption('json'))) {
				$output->writeln($bufferedOutput->fetch());
			} else {
				/** @var Document $document */
				$document = Parser::parseResponseString($bufferedOutput->fetch());
				if ($document->get('data.attributes.replicas') === 1) {
					$output->writeln('<info>App ' . $document->get('data.id') . ', has been enabled</info>');
				} else {
					$output->writeln('<info>App ' . $document->get('data.id') . ', has been disabled</info>');
				}
			}
		} else {
			$output->writeln('<error>' . $bufferedOutput->fetch() . '</error>');
			return 1;
		}

	}
}