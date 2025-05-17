<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 *
 */
#[AsCommand(
    name: 'app:refresh',
    description: 'Generate migration, run it, load fixtures, and clear the cache.',
)]
class RefreshCommand extends Command
{
    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $commands = [
            ['php', 'bin/console', 'doctrine:migrations:migrate', '-n'],
            ['php', 'bin/console', 'doctrine:fixtures:load', '-n'],
            ['php', 'bin/console', 'cache:clear', '-n'],
        ];

        foreach ($commands as $command) {
            $io->section('Running: ' . implode(' ', $command));

            $process = new Process($command);
            $process->setTty(Process::isTtySupported());

            $process->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                $io->error('Error while executing: ' . implode(' ', $command));
                return Command::FAILURE;
            }
        }

        $io->success('All steps completed successfully, including cache clear!');
        return Command::SUCCESS;
    }
}
