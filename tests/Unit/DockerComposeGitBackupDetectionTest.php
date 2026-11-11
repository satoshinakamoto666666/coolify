<?php

namespace Tests\Unit;

use App\Services\DockerComposeDatabaseDetector;
use PHPUnit\Framework\TestCase;

class DockerComposeGitBackupDetectionTest extends TestCase
{
    public function test_detects_database_images_across_registries_and_tags(): void
    {
        $this->assertTrue(DockerComposeDatabaseDetector::isDatabaseImage('postgres:16-alpine'));
        $this->assertTrue(DockerComposeDatabaseDetector::isDatabaseImage('docker.io/library/mariadb:11.4'));
        $this->assertTrue(DockerComposeDatabaseDetector::isDatabaseImage('pgvector/pgvector:pg16'));
        $this->assertTrue(DockerComposeDatabaseDetector::isDatabaseImage('mongo:7.0'));
        $this->assertFalse(DockerComposeDatabaseDetector::isDatabaseImage('nginx:alpine'));
        $this->assertFalse(DockerComposeDatabaseDetector::isDatabaseImage('node:20-slim'));
    }

    public function test_extracts_database_services_from_git_docker_compose_for_backups(): void
    {
        $compose = <<<'YAML'
services:
  web:
    build: .
    ports:
      - "3000:3000"
  db:
    image: postgres:16-alpine
    volumes:
      - pgdata:/var/lib/postgresql/data
  cache:
    image: redis:7-alpine
    volumes:
      - redisdata:/data
volumes:
  pgdata:
  redisdata:
YAML;

        $detected = DockerComposeDatabaseDetector::extractDatabaseServicesFromCompose($compose);
        $this->assertCount(2, $detected);
        $this->assertArrayHasKey('db', $detected);
        $this->assertSame('postgresql', $detected['db']['database_type']);
        $this->assertArrayHasKey('cache', $detected);
        $this->assertSame('redis', $detected['cache']['database_type']);
    }
}
