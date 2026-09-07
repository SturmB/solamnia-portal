<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Any failure talking to LLDAP: transport, login, or a GraphQL `errors` array.
 * The message is whatever LLDAP said, so it can be forwarded to Pushover as-is.
 */
class LldapException extends RuntimeException
{
    /**
     * LLDAP has no typed error codes; a duplicate `createUser` surfaces as a
     * uniqueness message. Only reachable through a race, since the controller
     * looks the username up before creating.
     */
    public function isDuplicateUser(): bool
    {
        // ponytail: message sniffing; replace if LLDAP ever grows error codes.
        return (bool) preg_match('/already exists|unique constraint/i', $this->getMessage());
    }
}
