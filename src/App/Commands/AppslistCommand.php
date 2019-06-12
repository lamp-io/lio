<?php
namespace Console\App\Commands;
 
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
 
class AppslistCommand extends Command
{
    protected static $defaultName = 'apps:list';

    protected function configure()
    {
        $this
            ->setDescription('gets the set of apps from the org associated with your token')
            ->setHelp('try rebooting')
        ;
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $token = file_get_contents('/root/.config/lamp.io/token');
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_URL, "https://api.lamp.io/apps");
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $token",
            'Content-type: application/vnd.api+json',
            'Accept: application/vnd.api+json'
        ));

        $output->writeln(curl_exec($curl_handle));

        curl_close($curl_handle);
    }
}

