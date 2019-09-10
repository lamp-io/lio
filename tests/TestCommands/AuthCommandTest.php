<?php

namespace Tests\TestCommands;

use Console\App\Commands\AuthCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AuthCommandTest extends TestCase
{
	private $application;

	public function setUp(): void
	{
		$this->application = new Application();
		$this->application->add(new AuthCommand());
	}

	public function testAuth()
	{
		$command = $this->application->find(AuthCommand::getDefaultName());
		$commandTester = new CommandTester($command);
		$commandTester->execute([
				'command' => AuthCommand::getDefaultName(),
			]
		);
		$this->assertEquals('0', $commandTester->getStatusCode());
	}
}