<?php

namespace App\Services;

use App\Exceptions\LldapException;
use LDAP\Connection;
use SensitiveParameter;

/**
 * A logic-free wrapper over ext-ldap's global functions, so tests can mock the
 * LDAP boundary the way Http::fake() stands in for the GraphQL one.
 */
class LdapConnection
{
    private readonly Connection $connection;

    public function __construct()
    {
        // An unset URL would silently fall back to libldap's default host.
        $url = config('services.lldap.ldap_url') ?: throw new LldapException('LLDAP_LDAP_URL is not set.');

        $this->connection = ldap_connect($url)
            ?: throw new LldapException('LLDAP_LDAP_URL is not a valid LDAP URL.');

        // Password Modify is an LDAPv3 extended operation.
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_NETWORK_TIMEOUT, 5);
    }

    /**
     * The `@` stops PHP's failure warning becoming an ErrorException, so the
     * caller can turn the `false` into an LldapException instead.
     */
    public function bind(string $dn, #[SensitiveParameter] string $password): bool
    {
        return @ldap_bind($this->connection, $dn, $password);
    }

    public function exopPasswd(string $dn, #[SensitiveParameter] string $newPassword): bool
    {
        return @ldap_exop_passwd($this->connection, $dn, '', $newPassword) === true;
    }

    public function error(): string
    {
        return ldap_error($this->connection);
    }
}
