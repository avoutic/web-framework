<?php

namespace WebFramework\Security;

use WebFramework\Core\StoredUserValuesFactory;
use WebFramework\Entity\User;

class SecurityIteratorService
{
    public function __construct(
        private StoredUserValuesFactory $storedUserValuesFactory,
    ) {}

    public function incrementFor(User $user): int
    {
        $storedValues = $this->storedUserValuesFactory->get($user->getId(), 'account');

        $securityIterator = (int) $storedValues->getValue('security_iterator', '0');
        $securityIterator++;
        $storedValues->setValue('security_iterator', (string) $securityIterator);

        return $securityIterator;
    }

    public function getFor(User $user): int
    {
        $storedValues = $this->storedUserValuesFactory->get($user->getId(), 'account');

        return (int) $storedValues->getValue('security_iterator', '0');
    }
}
