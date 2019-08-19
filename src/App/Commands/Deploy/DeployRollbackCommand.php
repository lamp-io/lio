<?php

namespace Console\App\Commands\Deploy;

use Console\App\Commands\Command;
use Console\App\Commands\DeployCommand;
use Console\App\Deploy\DeployInterface;
use Console\App\Helpers\ConfigHelper;
use Console\App\Helpers\DeployHelper;
use Exception;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeployRollbackCommand extends Command
{
	protected static $defaultName = 'deploy:rollback';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Rollback deploy to previous one')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->setHelp('try rebooting');
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

		/** @var QuestionHelper $questionHelper */
		$questionHelper = $this->getHelper('question');
		$question = new ConfirmationQuestion('<info>Are you sure that you want to make rollback to previous deploy version? (Y/n):</info>');
		if (!$questionHelper->ask($input, $output, $question)) {
			return 0;
		}

		if (!DeployHelper::isReleasesFolderExists($input->getArgument('app_id'), $this->getApplication())) {
			throw new Exception('Your app hasn\'t not contain any deploys yet');
		}
		$releases = DeployHelper::getReleases($input->getArgument('app_id'), $this->getApplication());
		if (count($releases) <= 0) {
			throw new Exception('Your app has only one deploy');
		}
		$previousRelease = $releases[count($releases) - 2];
		$configYaml = DeployHelper::getReleaseConfigContent($input->getArgument('app_id'), $previousRelease['id'], $this->getApplication());
		if (empty($configYaml)) {
			throw new Exception('lamp.io.yaml not found on a remote app, path ' . $previousRelease['id']);
		}
		$config = ConfigHelper::yamlToArray($configYaml);
		$type = $this->getDeployType($config);
		if (!array_key_exists($type, DeployCommand::DEPLOYS)) {
			throw new Exception('Invalid type inside of a lamp.io.yaml ' . $type . ', allowed types:' . implode(',', array_keys(DeployCommand::DEPLOYS)));
		}
		$currentRelease = DeployHelper::getActiveRelease($config['app']['id'], $this->getApplication());
		$deployObject = $this->getDeployObject($type, $config);
		try {
			$deployObject->revert($currentRelease . '/', $previousRelease['id'] . '/');
			$output->writeln('<info>Done check it out at https://' . $config['app']['id'] . '.lamp.app/</info>');
		} catch (Exception $exception) {
			$output->writeln('<error>' . trim($exception->getMessage()) . '</error>');
			$deployObject->revertProcess();
			$output->writeln(PHP_EOL . '<comment>Revert completed</comment>');
			return 1;
		}
	}

	/**
	 * @param string $type
	 * @param array $config
	 * @return DeployInterface
	 * @throws Exception
	 */
	protected function getDeployObject(string $type, array $config): DeployInterface
	{
		$deployClass = (DeployCommand::DEPLOYS[$type]);
		return new $deployClass($this->getApplication(), $config);
	}

	/**
	 * @param array $config
	 * @return string
	 */
	protected function getDeployType(array $config): string
	{
		return !empty($config['type']) ? $config['type'] : '';
	}

}