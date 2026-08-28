<?php

namespace App\Command;

use App\Catalog\ApplicationCatalog;
use App\Entity\Application;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-applications',
    description: 'Load default applications into the database'
)]
class LoadApplicationsCommand extends Command
{
    public function __construct(
        private ApplicationRepository $applicationRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Loading Applications');

        $applicationsData = ApplicationCatalog::APPLICATIONS;

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($applicationsData as $data) {
            $app = $this->applicationRepository->findOneBy(['name' => $data['name']]);
            
            if (!$app) {
                $app = new Application();
                $app->setName($data['name']);
                $this->entityManager->persist($app);
                $createdCount++;
                $io->text(sprintf('  • Creating application: <info>%s</info>', $data['name']));
            } else {
                // Check if update is needed
                if ($app->getDescription() === $data['description'] &&
                    $app->getUrl() === $data['url'] &&
                    $app->getIconUrl() === $data['iconUrl'] &&
                    $app->getDatabaseName() === $data['databaseName'] &&
                    $app->isActive() === $data['isActive']) {
                    $skippedCount++;
                    $io->text(sprintf('  • Skipping (already up-to-date): <comment>%s</comment>', $data['name']));
                    continue;
                }
                $updatedCount++;
                $io->text(sprintf('  • Updating application: <info>%s</info>', $data['name']));
            }
            
            // Set/update common properties
            $app->setDescription($data['description']);
            $app->setUrl($data['url']);
            $app->setIconUrl($data['iconUrl']);
            $app->setDatabaseName($data['databaseName']);
            $app->setIsActive($data['isActive']);
        }

        $this->entityManager->flush();

        $io->newLine();
        $io->success('Applications loaded successfully!');
        $io->table(
            ['Action', 'Count'],
            [
                ['Created', $createdCount],
                ['Updated', $updatedCount],
                ['Skipped', $skippedCount],
                ['Total', count($applicationsData)],
            ]
        );

        return Command::SUCCESS;
    }
}
