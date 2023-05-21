<?php

namespace WebFramework\Security;

use WebFramework\Core\User;

interface AuthenticationService
{
    public function cleanup(): void;

    public function is_authenticated(): bool;

    public function authenticate(User $user): void;

    public function deauthenticate(): void;

    public function invalidate_sessions(int $user_id): void;

    public function get_authenticated_user(): User;

    /**
     * @param array<string> $permissions
     */
    public function user_has_permissions(array $permissions): bool;
}
