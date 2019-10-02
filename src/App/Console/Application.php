<?php


namespace Lio\App\Console;

use Composer\Autoload\ClassLoader;
use GuzzleHttp\Client;
use Lio\App\Commands\AuthCommand;
use Lio\App\Commands\Command;
use Lio\App\Commands\SelfUpdateCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Application extends BaseApplication
{
	const SKIP_COMMANDS = [
		Command::class,
	];

	const NO_HTTP_CLIENT_COMMANDS = [
		SelfUpdateCommand::class, AuthCommand::class,
	];

	/**
	 * @var ClassLoader|null
	 */
	private $classLoader;

	/**
	 * Application constructor.
	 * @param ClassLoader|null $classLoader
	 * @param string $name
	 * @param string $version
	 */
	public function __construct(ClassLoader $classLoader = null, string $name = 'UNKNOWN', string $version = 'UNKNOWN')
	{
		parent::__construct($name, $version);
		$this->classLoader = $classLoader;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws Throwable
	 */
	public function doRun(InputInterface $input, OutputInterface $output)
	{
		$this->addCommands($this->getCommandsObjects($this->getCommandsListArray()));
		return parent::doRun($input, $output);
	}

	/**
	 * @param array $commands
	 */
	public function addCommands(array $commands)
	{
		parent::addCommands($commands);
	}

	protected function getDefaultInputDefinition()
	{
		$inputDefinition = parent::getDefaultInputDefinition();
		$inputDefinition->addOptions([
			new InputOption('json', 'j', InputOption::VALUE_NONE, 'Output as a raw json'),
			new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'No output'),
		]);

		return $inputDefinition;
	}

	/**
	 * @return array
	 */
	private function getCommandsListArray()
	{
		return array_keys(array_filter($this->classLoader->getClassMap(), function ($key) {
			return strpos($key, 'Lio\\App\\Commands\\') !== false;

		}, ARRAY_FILTER_USE_KEY));
	}

	/**
	 * @param array $commandsList
	 * @return array
	 */
	private function getCommandsObjects(array $commandsList): array
	{
		$commands = [];
		foreach ($commandsList as $namespace) {
			if (in_array($namespace, self::SKIP_COMMANDS)) {
				continue;
			}
			if (in_array($namespace, self::NO_HTTP_CLIENT_COMMANDS)) {
				$commands[] = new $namespace();
			} else {
				$commands[] = new $namespace(new Client());
			}
		}
		return $commands;
	}
}