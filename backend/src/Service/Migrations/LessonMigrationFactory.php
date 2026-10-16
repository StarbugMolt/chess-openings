<?php

declare(strict_types=1);

namespace App\Service\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LessonMigrationFactory
{
    public function __construct(private Connection $connection, #[Autowire('%kernel.project_dir%')] private string $projectDirectory)
    {
    }

    public function create(): DependencyFactory
    {
        return DependencyFactory::fromConnection(new ConfigurationArray([
            'migrations_paths' => ['AppMigrations\\LessonData' => $this->projectDirectory.'/migrations/LessonData'],
            'table_storage' => ['table_name' => 'doctrine_lesson_versions'],
            'all_or_nothing' => true,
            'transactional' => true,
        ]), new ExistingConnection($this->connection));
    }
}
