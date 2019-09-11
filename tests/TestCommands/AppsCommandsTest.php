<?php


namespace Tests\TestCommands;


use Console\App\Commands\Apps\AppsDeleteCommand;
use Console\App\Commands\Apps\AppsDescribeCommand;
use Console\App\Commands\Apps\AppsListCommand;
use Console\App\Commands\Apps\AppsNewCommand;
use Console\App\Commands\Apps\AppsUpdateCommand;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AppsCommandsTest extends TestCase
{
	const TEST_APP_ID = 'phpunit-app-test-id';

	private $application;

	public function setUp(): void
	{
		$this->application = new Application();
	}

	private function getMockResponse(string $body, int $httpCode = 200, array $headers = []): Response
	{
		return new Response($httpCode, $headers, $body);
	}

	private function getMockedClient(Response $response): Client
	{
		return new Client([
			'handler' => HandlerStack::create(new MockHandler([
				$response,
			])),
		]);
	}

	private function getExecutedCommandTester(Command $command, array $input = []): CommandTester
	{
		$commandTester = new CommandTester($command);
		$commandTester->execute(array_merge(['command' => $command], $input));
		return $commandTester;
	}

	public function testAppsDeleteCommand()
	{
		$client = $this->getMockedClient($this->getMockResponse(''));
		$command = $this->application->add(new AppsDeleteCommand($client, null, true));
		$commandTester = $this->getExecutedCommandTester($command, ['app_id' => self::TEST_APP_ID, '--yes' => true]);
		$this->assertEquals('0', $commandTester->getStatusCode());
	}

	public function testAppsListCommand()
	{
		$client = $this->getMockedClient($this->getMockResponse(''));
		$command = $this->application->add(new AppsListCommand($client, null, true));
		$commandTester = $this->getExecutedCommandTester($command, ['--json' => true]);
		$this->assertEquals('0', $commandTester->getStatusCode());
	}

	public function testAppsDescribeCommand()
	{
		$client = $this->getMockedClient($this->getMockResponse(''));
		$command = $this->application->add(new AppsDescribeCommand($client, null, true));
		$commandTester = $this->getExecutedCommandTester($command, ['--json' => true, 'app_id' => self::TEST_APP_ID]);
		$this->assertEquals('0', $commandTester->getStatusCode());
	}

	public function testAppsUpdateCommand()
	{
		$client = $this->getMockedClient($this->getMockResponse(json_encode([
				'data' => [
					'id'   => self::TEST_APP_ID,
					'type' => 'apps',
				],
			]
		)));
		$this->application->add(new AppsUpdateCommand($client, null, true));
		$command = $this->application->find(AppsUpdateCommand::getDefaultName());
		$phpIni = getcwd() . DIRECTORY_SEPARATOR . 'php.ini';
		$httpdConf = getcwd() . DIRECTORY_SEPARATOR . 'http.conf';
		file_put_contents($phpIni, '');
		file_put_contents($httpdConf, '');
		try {
			$commandTester = $this->getExecutedCommandTester($command, [
				'app_id'                       => self::TEST_APP_ID,
				'--json'                       => true,
				'--description'                => 'phpunit',
				'--httpd_conf'                 => $httpdConf,
				'--max_replicas'               => '1',
				'--memory'                     => '128Mi',
				'--min_replicas'               => '1',
				'--php_ini'                    => $phpIni,
				'--replicas'                   => '1',
				'--vcpu'                       => '0.25',
				'--github_webhook_secret'      => 'phpunit',
				'--webhook_run_command'        => 'phpunit',
				'--hostname'                   => 'phpunit',
				'--hostname_certificate_valid' => 'phpunit',
				'--public'                     => true,
			]);
			$status = $commandTester->getStatusCode();
		} catch (Exception $exception) {
			$status = $exception->getMessage();
		}
		unlink($phpIni);
		unlink($httpdConf);
		$this->assertEquals('0', $status);
	}

	public function testAppsNewCommand()
	{
		$client = $this->getMockedClient($this->getMockResponse(json_encode([
				'data' => [
					'id'   => self::TEST_APP_ID,
					'type' => 'apps',
				],
			]
		)));
		$this->application->add(new AppsNewCommand($client, null, true));
		$command = $this->application->find(AppsNewCommand::getDefaultName());
		$phpIni = getcwd() . DIRECTORY_SEPARATOR . 'php.ini';
		$httpdConf = getcwd() . DIRECTORY_SEPARATOR . 'http.conf';
		file_put_contents($phpIni, '');
		file_put_contents($httpdConf, '');
		try {
			$commandTester = $this->getExecutedCommandTester($command, [
				'--json'                       => true,
				'organization_id'              => 'phpunit',
				'--description'                => 'phpunit',
				'--httpd_conf'                 => $httpdConf,
				'--max_replicas'               => '1',
				'--memory'                     => '128Mi',
				'--min_replicas'               => '1',
				'--php_ini'                    => $phpIni,
				'--replicas'                   => '1',
				'--vcpu'                       => '0.25',
				'--github_webhook_secret'      => 'phpunit',
				'--webhook_run_command'        => 'phpunit',
				'--hostname'                   => 'phpunit',
				'--hostname_certificate_valid' => 'phpunit',
				'--public'                     => true,
			]);
			$status = $commandTester->getStatusCode();
		} catch (Exception $exception) {
			$status = $exception->getMessage();
		}
		unlink($phpIni);
		unlink($httpdConf);
		$this->assertEquals('0', $status);
	}
}