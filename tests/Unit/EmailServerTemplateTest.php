<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class EmailServerTemplateTest extends TestCase
{
    public function test_docker_mailserver_template_defines_persistent_volumes_and_proxy_fqdn(): void
    {
        $path = dirname(__DIR__, 2) . '/templates/compose/docker-mailserver.yaml';
        $this->assertFileExists($path);

        $parsed = Yaml::parseFile($path);
        $this->assertArrayHasKey('services', $parsed);
        $this->assertArrayHasKey('mailserver', $parsed['services']);
        $this->assertArrayHasKey('webmail', $parsed['services']);
        $this->assertArrayHasKey('dms-mail-data', $parsed['volumes']);
        $this->assertArrayHasKey('dms-mail-config', $parsed['volumes']);
        $this->assertArrayHasKey('dms-certs', $parsed['volumes']);
    }

    public function test_stalwart_mail_template_defines_multi_domain_storage_and_certs(): void
    {
        $path = dirname(__DIR__, 2) . '/templates/compose/stalwart-mail.yaml';
        $this->assertFileExists($path);

        $parsed = Yaml::parseFile($path);
        $this->assertArrayHasKey('stalwart-mail', $parsed['services']);
        $this->assertArrayHasKey('stalwart-data', $parsed['volumes']);
        $this->assertArrayHasKey('stalwart-certs', $parsed['volumes']);
    }
}
