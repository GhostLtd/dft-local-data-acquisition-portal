<?php

namespace App\Command;

use App\Features;
use App\Utility\Screenshots\FixtureManager;
use App\Utility\Screenshots\SubprocessRequestHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'ldap:screenshots', description: 'Generate screenshots for the LDAP website frontend')]
class ScreenshotsCommand extends Command
{
    public function __construct(
        protected EntityManagerInterface           $entityManager,
        protected Features                         $features,
        protected FixtureManager                   $fixtureCreator,
        protected SubprocessRequestHelper          $subprocessRequestHelper,
        protected UserPasswordHasherInterface      $passwordHasher,
        protected string                           $frontendHostname,
        protected string                           $screenshotsPath,
        protected string                           $appEnvironment
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('protocol', null, InputOption::VALUE_OPTIONAL, 'http or https', 'https')
            ->addOption('no-build', null, InputOption::VALUE_NONE, 'disable "yarn build" step')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cursor = new Cursor($output);

        if ($this->appEnvironment !== 'prod') {
            throw new \RuntimeException("The screenshots command must be run in the `prod` environment");
        }

        $noBuild = $input->getOption('no-build');

        if (!$noBuild) {
            $io->writeln('<question>Running "yarn build"...</question>');

            $executableFinder = new ExecutableFinder();
            $yarnPath = $executableFinder->find('yarn');

            if (!$yarnPath) {
                throw new \RuntimeException('Unable to locate yarn binary');
            }

            $terminal = new Terminal();
            $maxLineLength = $terminal->getWidth();

            $process = new Process([$yarnPath, "build"]);
            $process->setTimeout(3600);

            try {
                $process->mustRun(function($type, $buffer) use ($io, $cursor, $maxLineLength) {
                    if (Process::ERR === $type) {
                        $parts = explode('<s>', $buffer);
                        foreach($parts as $part) {
                            if (trim($part) === '') {
                                continue;
                            }

                            $buffer = str_replace('<s>', "", $part);
                            $buffer = str_replace("\n", "", $buffer);
                            $buffer = mb_str_split($buffer, $maxLineLength - 1)[0];

                            $io->write("\r<info>yarn build</info> $buffer");
                            $cursor->clearLineAfter();
                        }
                    } else {
                        $io->write("\r<comment>yarn build</comment> $buffer");
                    }
                });
            } catch (ProcessFailedException $exception) {
                echo $exception->getMessage();
            }
        }

        $path = dirname(__FILE__).'/../../screenshots/screenshots.js';

        if (!file_exists($path)) {
            throw new \RuntimeException("Screenshots JS command could not be found");
        }

        $outputBaseDir = realpath(dirname($path)).'/output';
        if (!is_dir($outputBaseDir)) {
            throw new \RuntimeException("Screenshots output directory missing (Expected at {$outputBaseDir})");
        }

        $outputDir = "{$outputBaseDir}/" . (new \DateTime())->format('Ymd-His');

        $protocol = $input->getOption('protocol');
        $processArgs = [
            $path,
            "frontend",
            "{$protocol}://{$this->frontendHostname}/",
            "{$outputDir}/",
        ];

        $input = new InputStream();

        $io->writeln('<question>Running screenshots...</question>');
        $process = new Process($processArgs);
        $process->setTimeout(3600);
        $process->setInput($input);

        try {
            $process->disableOutput();
            $process->mustRun(function($type, $buffer) use ($input, $io): void {
                $this->subprocessRequestHelper->process($buffer, $input, function($line) use ($io) {
                    $io->writeln("\r<comment>screenshots</comment> $line");
                });
            });
        } catch (ProcessFailedException $exception) {
            echo $exception->getMessage();
        }

        return Command::SUCCESS;
    }
}
