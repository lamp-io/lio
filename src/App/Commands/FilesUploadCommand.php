<?php


namespace Console\App\Commands;


use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUploadCommand extends Command
{
    const API_ENDPOINT = 'https://api.lamp.io/apps/{app_id}/files';

    protected static $defaultName = 'files:upload';

    protected function configure()
    {
        $this->setDescription('Creates new file')
            ->setHelp('https://www.lamp.io/api#/files/filesCreate')
            ->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to file, that should be uploaded');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        try {


        } catch (GuzzleException $guzzleException) {
            $output->writeln($guzzleException->getMessage());
        }

    }
}