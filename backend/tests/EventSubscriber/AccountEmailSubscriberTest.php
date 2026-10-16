<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\AccountEmailSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class AccountEmailSubscriberTest extends TestCase
{
    public function testPublicRequestsDoNotResolveAccountMailOrItsEnvironmentConfiguration(): void
    {
        $subscriber = new AccountEmailSubscriber(static function (): never {
            self::fail('A catalogue request instantiated account mail services.');
        });
        $subscriber->deliver(new TerminateEvent($this->createMock(HttpKernelInterface::class), Request::create('/api/openings'), new Response()));
        self::assertTrue(true);
    }
}
