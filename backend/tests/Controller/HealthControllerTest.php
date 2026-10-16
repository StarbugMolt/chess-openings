<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthControllerTest extends WebTestCase
{
    public function testReadinessUsesTheDatabaseAndDoesNotCache(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/health');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertStringContainsString('no-store', (string) $client->getResponse()->headers->get('Cache-Control'));
        self::assertJsonStringEqualsJsonString('{"status":"ok"}', (string) $client->getResponse()->getContent());
    }

    public function testMissingApiRoutesReturnJson(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/not-a-route');

        self::assertResponseStatusCodeSame(404);
        self::assertJsonStringEqualsJsonString('{"error":"Not found."}', (string) $client->getResponse()->getContent());
    }

    public function testHealthIsReadOnly(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/health');

        self::assertResponseStatusCodeSame(405);
        self::assertResponseHasHeader('Allow');
    }
}
