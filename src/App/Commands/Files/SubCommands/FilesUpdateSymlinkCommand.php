<?php


namespace Lio\App\Commands\Files\SubCommands;


use Exception;
use Lio\App\AbstractCommands\AbstractUpdateCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateSymlinkCommand extends AbstractUpdateCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/';

	protected static $defaultName = 'files:update:symlink';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update a symlink on your app')
			->setHelp('Create a symlink, api reference' . PHP_EOL . 'https://www.lamp.io/api#/files/filesUpdate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file_id', InputArgument::REQUIRED, 'File ID of a symlink to update')
			->addArgument('target', InputArgument::REQUIRED, 'Symlink target file ID')
			->addOption('apache_writable', null, InputOption::VALUE_REQUIRED, 'Allow apache to write to the file ID')
			->setBoolOptions(['apache_writable']);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('app_id')
		));
		return parent::execute($input, $output);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		$output->writeln('<info>Success, symlink ' . $input->getArgument('file_id') . ' has been updated</info>');
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$body = [
			'data' => [
				'type'       => 'files',
				'id'         => trim($input->getArgument('file_id'), '/'),
				'attributes' => [
					'is_symlink' => true,
					'target'     => trim($input->getArgument('target'), '/'),
				],
			],
		];
		if (!empty($input->getOption('apache_writable'))) {
			$body['data']['attributes']['apache_writable'] = $input->getOption('apache_writable') != 'false';
		}
		return json_encode($body);
	}
}