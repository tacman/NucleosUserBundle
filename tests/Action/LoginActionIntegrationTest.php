<?php

declare(strict_types=1);

/*
 * This file is part of the NucleosUserBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserBundle\Tests\Action;

use Nucleos\UserBundle\Tests\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers \Nucleos\UserBundle\Action\LoginAction
 */
final class LoginActionIntegrationTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testLogin(): void
    {
        $this->client->followRedirects(true);
        $this->client->request('GET', '/login');

        static::assertResponseStatusCodeSame(200);
    }

    protected static function getKernelClass(): string
    {
        return AppKernel::class;
    }
}
