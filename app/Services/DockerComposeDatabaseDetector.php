<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;

class DockerComposeDatabaseDetector
{
    /**
     * Canonical database container images supported for automated scheduled backups in Coolify.
     *
     * @var array<int, string>
     */
    public const DATABASE_IMAGE_PREFIXES = [
        'postgres',
        'postgis/postgis',
        'pgvector/pgvector',
        'timescale/timescaledb',
        'supabase/postgres',
        'bitnami/postgresql',
        'mysql',
        'bitnami/mysql',
        'mariadb',
        'bitnami/mariadb',
        'mongo',
        'bitnami/mongodb',
        'redis',
        'valkey/valkey',
        'dragonflydb/dragonfly',
        'clickhouse/clickhouse-server',
    ];

    /**
     * Determines whether a Docker image reference corresponds to a backup-eligible database engine.
     */
    public static function isDatabaseImage(?string $image): bool
    {
        if ($image === null || trim($image) === '') {
            return false;
        }

        $normalized = strtolower(trim($image));
        // Strip registry host if present (e.g., docker.io/library/postgres:16-alpine -> postgres)
        $withoutTag = explode(':', $normalized)[0];
        $withoutDigest = explode('@', $withoutTag)[0];
        $clean = preg_replace('#^(docker\.io/|ghcr\.io/|quay\.io/)?(library/)?#', '', $withoutDigest) ?? $withoutDigest;

        foreach (self::DATABASE_IMAGE_PREFIXES as $prefix) {
            if ($clean === $prefix || str_starts_with($clean, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Classifies the canonical database type for backup scheduling (`postgresql`, `mysql`, `mariadb`, `mongodb`, `redis`, `clickhouse`).
     */
    public static function detectDatabaseType(string $image): ?string
    {
        if (! self::isDatabaseImage($image)) {
            return null;
        }

        $lower = strtolower($image);
        return match (true) {
            str_contains($lower, 'postgres'), str_contains($lower, 'postgis'), str_contains($lower, 'pgvector'), str_contains($lower, 'timescale') => 'postgresql',
            str_contains($lower, 'mariadb') => 'mariadb',
            str_contains($lower, 'mysql') => 'mysql',
            str_contains($lower, 'mongo') => 'mongodb',
            str_contains($lower, 'redis'), str_contains($lower, 'valkey'), str_contains($lower, 'dragonfly') => 'redis',
            str_contains($lower, 'clickhouse') => 'clickhouse',
            default => 'database',
        };
    }

    /**
     * Parses a Git-based Application Docker Compose YAML string and extracts all database service definitions
     * so `ServiceDatabase` records and scheduled backups can be provisioned identically to `Service` deployments.
     *
     * @return array<string, array{name: string, image: string, database_type: string, volumes: array<int, string>}>
     */
    public static function extractDatabaseServicesFromCompose(string $dockerComposeYaml): array
    {
        if (trim($dockerComposeYaml) === '') {
            return [];
        }

        $parsed = Yaml::parse($dockerComposeYaml);
        if (! is_array($parsed) || ! isset($parsed['services']) || ! is_array($parsed['services'])) {
            return [];
        }

        $databases = [];
        foreach ($parsed['services'] as $serviceName => $serviceConfig) {
            if (! is_array($serviceConfig)) {
                continue;
            }
            $image = isset($serviceConfig['image']) ? (string) $serviceConfig['image'] : '';
            if (! self::isDatabaseImage($image)) {
                continue;
            }

            $dbType = self::detectDatabaseType($image) ?? 'database';
            $volumes = [];
            if (isset($serviceConfig['volumes']) && is_array($serviceConfig['volumes'])) {
                foreach ($serviceConfig['volumes'] as $vol) {
                    if (is_string($vol)) {
                        $volumes[] = $vol;
                    } elseif (is_array($vol) && isset($vol['source'])) {
                        $volumes[] = (string) $vol['source'] . ':' . (string) ($vol['target'] ?? '');
                    }
                }
            }

            $databases[(string) $serviceName] = [
                'name' => (string) $serviceName,
                'image' => $image,
                'database_type' => $dbType,
                'volumes' => $volumes,
            ];
        }

        return $databases;
    }
}
