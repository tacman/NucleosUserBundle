<?php

/*
 * This file is part of the NucleosUserBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserBundle\Action;

use DateTime;
use Nucleos\UserBundle\Event\GetResponseNullableUserEvent;
use Nucleos\UserBundle\Event\GetResponseUserEvent;
use Nucleos\UserBundle\Mailer\MailerInterface;
use Nucleos\UserBundle\Model\UserInterface;
use Nucleos\UserBundle\Model\UserManagerInterface;
use Nucleos\UserBundle\NucleosUserEvents;
use Nucleos\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SendEmailAction
{
    private RouterInterface $router;

    private EventDispatcherInterface $eventDispatcher;

    private UserManagerInterface $userManager;

    private TokenGeneratorInterface $tokenGenerator;

    private MailerInterface $mailer;

    private int $retryTtl;

    private UserProviderInterface $userProvider;

    public function __construct(
        RouterInterface $router,
        EventDispatcherInterface $eventDispatcher,
        UserManagerInterface $userManager,
        TokenGeneratorInterface $tokenGenerator,
        UserProviderInterface $userProvider,
        MailerInterface $mailer,
        int $retryTtl
    ) {
        $this->router          = $router;
        $this->eventDispatcher = $eventDispatcher;
        $this->userManager     = $userManager;
        $this->tokenGenerator  = $tokenGenerator;
        $this->userProvider    = $userProvider;
        $this->mailer          = $mailer;
        $this->retryTtl        = $retryTtl;
    }

    public function __invoke(Request $request): Response
    {
        $username = (string) $request->request->get('username', '');

        $user = null;

        try {
            $user = '' === $username ? null : $this->userProvider->loadUserByIdentifier($username);
        } catch (UserNotFoundException) {
        }

        if ($user instanceof UserInterface) {
            $response = $this->process($request, $user);

            if (null !== $response) {
                return $response;
            }
        }

        return new RedirectResponse($this->router->generate('nucleos_user_resetting_check_email', [
            'username' => $username,
        ]));
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function process(Request $request, UserInterface $user): ?Response
    {
        $event = new GetResponseNullableUserEvent($user, $request);
        $this->eventDispatcher->dispatch($event, NucleosUserEvents::RESETTING_SEND_EMAIL_INITIALIZE);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        if ($user->isPasswordRequestNonExpired($this->retryTtl)) {
            return null;
        }

        $event = new GetResponseUserEvent($user, $request);
        $this->eventDispatcher->dispatch($event, NucleosUserEvents::RESETTING_RESET_REQUEST);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($this->tokenGenerator->generateToken());
        }

        $event = new GetResponseUserEvent($user, $request);
        $this->eventDispatcher->dispatch($event, NucleosUserEvents::RESETTING_SEND_EMAIL_CONFIRM);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $this->mailer->sendResettingEmailMessage($user);
        $user->setPasswordRequestedAt(new DateTime());
        $this->userManager->updateUser($user);

        $event = new GetResponseUserEvent($user, $request);
        $this->eventDispatcher->dispatch($event, NucleosUserEvents::RESETTING_SEND_EMAIL_COMPLETED);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        return null;
    }
}
