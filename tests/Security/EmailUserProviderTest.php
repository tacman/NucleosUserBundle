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

namespace Nucleos\UserBundle\Tests\Security;

use Nucleos\UserBundle\Model\User;
use Nucleos\UserBundle\Model\UserInterface;
use Nucleos\UserBundle\Model\UserManager;
use Nucleos\UserBundle\Security\EmailUserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

final class EmailUserProviderTest extends TestCase
{
    /**
     * @var MockObject&UserManager
     */
    private UserManager $userManager;

    private EmailUserProvider $userProvider;

    protected function setUp(): void
    {
        $this->userManager  = $this->getMockBuilder(UserManager::class)->getMock();
        $this->userProvider = new EmailUserProvider($this->userManager);
    }

    public function testLoadUserByUsername(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $this->userManager->expects(static::once())
            ->method('findUserByUsername')
            ->with('foobar')
            ->willReturn($user)
        ;

        static::assertSame($user, $this->userProvider->loadUserByUsername('foobar'));
    }

    public function testLoadUserByInvalidUsername(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->userManager->expects(static::once())
            ->method('findUserByUsername')
            ->with('foobar')
            ->willReturn(null)
        ;

        $this->userProvider->loadUserByUsername('foobar');
    }

    public function testRefreshUserBy(): void
    {
        $user = $this->getMockBuilder(User::class)->getMock();

        $user->expects(static::once())
            ->method('getUserIdentifier')
            ->willReturn('123')
        ;

        $refreshedUser = $this->getMockBuilder(UserInterface::class)->getMock();
        $this->userManager->expects(static::once())
            ->method('findUserByUsername')
            ->with('123')
            ->willReturn($refreshedUser)
        ;

        $this->userManager->expects(static::atLeastOnce())
            ->method('getClass')
            ->willReturn(\get_class($user))
        ;

        static::assertSame($refreshedUser, $this->userProvider->refreshUser($user));
    }

    public function testRefreshInvalidUser(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $user = $this->getMockBuilder(SymfonyUserInterface::class)->getMock();

        $this->userProvider->refreshUser($user);
    }
}
