<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Snapshot;
use App\Domain\Url;
use App\Service\ContentFetcherInterface;
use App\Service\SnapshotStorage;
use Jfcherng\Diff\DiffHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'guaita:check', description: 'Check a monitored URL for changes')]
final class CheckUrlCommand extends Command
{
    public function __construct(
        private readonly ContentFetcherInterface $contentFetcher,
        private readonly SnapshotStorage $snapshotStorage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('url', InputArgument::REQUIRED, 'The URL to check');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = new SymfonyStyle($input, $output);
        $url = new Url($input->getArgument('url'));

        try {
            $newSnapshot = $this->contentFetcher->fetch($url);
        } catch (\Throwable $exception) {
            $console->error('Error en descarregar la URL: ' . $exception->getMessage());
            return Command::FAILURE;
        }

        $previousSnapshot = $this->snapshotStorage->findLatest($url);
        $this->snapshotStorage->store($url, $newSnapshot);

        return $this->report($console, $previousSnapshot, $newSnapshot);
    }

    private function report(SymfonyStyle $console, ?Snapshot $previous, Snapshot $current): int
    {
        if ($previous === null) {
            $console->success('Primera consulta. Contingut desat. Hash: ' . $current->contentHash()->toString());
            return Command::SUCCESS;
        }

        if ($previous->hasSameContentAs($current)) {
            $console->success('Cap canvi detectat. Hash: ' . $current->contentHash()->toString());
            return Command::SUCCESS;
        }

        return $this->showDiff($console, $previous, $current);
    }

    private function showDiff(SymfonyStyle $console, Snapshot $previous, Snapshot $current): int
    {
        $console->warning('El contingut ha canviat!');
        $console->definitionList(
            ['Hash anterior' => $previous->contentHash()->toString()],
            ['Hash nou'      => $current->contentHash()->toString()],
        );

        $diff = DiffHelper::calculate(
            $previous->content(),
            $current->content(),
            'Unified',
            ['context' => 3],
            ['lineNumbers' => true],
        );

        $console->writeln($diff);

        return Command::SUCCESS;
    }
}
