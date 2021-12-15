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

namespace Symfony\Component\Routing\Loader\Configurator;

use Nucleos\UserBundle\Action\ChangePasswordAction;

return static function (RoutingConfigurator $routes): void {
    $routes->add('nucleos_user_change_password', '/change-password')
        ->controller(ChangePasswordAction::class)
        ->methods(['GET', 'POST'])
    ;
};
