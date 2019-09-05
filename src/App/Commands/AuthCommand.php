<?php

namespace Console\App\Commands;

use Console\App\Helpers\AuthHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class AuthCommand extends Command
{
    const TOKEN_LENGTH = '52';

    const TOKEN_ATTEMPTS = '3';

    protected static $defaultName = 'auth';

    /**
     *
     */
    protected function configure()
    {
        $this->setDescription('Set auth token')
            ->setHelp('Get your token at https://www.lamp.io/ on settings page')
            ->addOption('update_token', '-u', InputOption::VALUE_NONE, 'Update existing token');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        if (AuthHelper::isTokenExist() && empty($input->getOption('update_token'))) {
            $output->writeln(' <info>Token already exist, if you want to update it, please add -u/--update_token option </info>');
        } else {
            $question = new Question('Please write your auth token' . PHP_EOL);
            $question->setValidator(function ($answer) {
                if (empty($answer) || strlen($answer) < self::TOKEN_LENGTH) {
                    throw new \RuntimeException(
                        'Invalid Token. Get your token at https://www.lamp.io/ on settings page'
                    );
                }
                return $answer;
            });
            $question->setMaxAttempts(self::TOKEN_ATTEMPTS);
            AuthHelper::saveToken($questionHelper->ask($input, $output, $question));
            $output->writeln('<info>Token saved</info>');
        }
    }
}
