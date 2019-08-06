<?php

namespace Console\App\Commands;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Apps\AppsNewCommand;
use Console\App\Deploy\DeployInterface;
use Console\App\Deploy\Laravel;
use Console\App\Helpers\ConfigHelper;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeployCommand extends Command
{
	/**
	 * @var string
	 */
	protected static $defaultName = 'deploy';

	const DEPLOYS = [
		'laravel' => Laravel::class,
	];

	/**
	 * @var ConfigHelper
	 */
	protected $configHelper;

	/**
	 * @var bool
	 */
	protected $isNewApp = true;

	public function __construct(ClientInterface $httpClient, $name = null)
	{
		parent::__construct($httpClient, $name);
	}

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Deploy your app.')
			->addArgument('dir', InputArgument::OPTIONAL, 'Path to a directory of your application, default value current working directory', getcwd())
			->addOption('laravel', null, InputOption::VALUE_NONE, 'The ID of the app');
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
		try {
			$appPath = rtrim($input->getArgument('dir'), '/') . DIRECTORY_SEPARATOR;
			$this->configHelper = new ConfigHelper($appPath);
			$releaseId = time();
			$this->configHelper->set('release', $releaseId);
			$deployObject = $this->getDeployObject($input->getOptions(), $appPath, $releaseId);
			$deployObject->isCorrectApp($appPath);
			$appId = $this->getAppId($output, $input);
			$this->configHelper->save();
			$deployObject->deployApp($appId, $this->isNewApp);
			$output->writeln('<info>Done, check it out at https://' . $appId . '.lamp.app/</info>');
		} catch (Exception $exception) {
			$output->writeln('<error>' . $exception->getMessage() . '</error>');
			return 1;
		}
	}

	/**
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return string
	 * @throws Exception
	 */
	protected function getAppId(OutputInterface $output, InputInterface $input): string
	{
		if (!empty($this->configHelper->get('app.id'))) {
			$this->isNewApp = false;
			return $this->configHelper->get('app.id');
		}

		$questionHelper = $this->getHelper('question');
		$question = new ConfirmationQuestion('<info>This looks like a new app, shall we create a lamp.io app for it? (Y/n):</info>');
		if (!$questionHelper->ask($input, $output, $question)) {
			$output->writeln('<info>You must to create new app or select to which app your project should be deployed, in lamp.io.yaml file inside of your project</info>');
			return 0;
		}
		$appsNewCommand = $this->getApplication()->find(AppsNewCommand::getDefaultName());
		$args = [
			'command' => AppsNewCommand::getDefaultName(),
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		$appsNewCommand->run(new ArrayInput($args), $bufferOutput);
		/** @var Document $document */
		$document = Parser::parseResponseString($bufferOutput->fetch());
		$appId = $document->get('data.id');
		$output->writeln('<info>' . $appId . ' created!</info>');
		$this->configHelper->set('app.id', $appId);
		return $appId;
	}


	/**
	 * @param array $options
	 * @param string $appDir
	 * @param int $releaseId
	 * @return DeployInterface
	 */
	protected function getDeployObject(array $options, string $appDir, int $releaseId): DeployInterface
	{
		foreach ($options as $optionKey => $option) {
			if ($option && array_key_exists($optionKey, self::DEPLOYS)) {
				$deployClass = (self::DEPLOYS[$optionKey]);
				return new $deployClass($appDir, $this->getApplication(), $releaseId);
			}
		}

		if (array_key_exists($this->configHelper->get('type'), self::DEPLOYS)) {
			$deployClass = (self::DEPLOYS[$this->configHelper->get('type')]);
			return new $deployClass($appDir, $this->getApplication(), $releaseId);
		}

		throw new InvalidArgumentException('App type for deployment, not specified, apps allowed ' . implode(',', array_keys(self::DEPLOYS)));
	}

}