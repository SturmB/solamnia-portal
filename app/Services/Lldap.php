<?php

namespace App\Services;

use App\Exceptions\LldapException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use SensitiveParameter;

/**
 * The portal's provisioning surface into LLDAP, using the dedicated service
 * account (never the directory superuser): GraphQL for users and groups, LDAP
 * for the one thing GraphQL can't do, setting a password. A JWT is fetched
 * per request: acceptances are rare, so caching it buys nothing.
 */
class Lldap
{
    public const string MEMBERS_GROUP = 'members';

    /**
     * @return array{id: string, email: string}|null
     */
    public function findUser(string $id): ?array
    {
        // `users(filters:)` returns an empty list for a miss, where `user(userId:)`
        // would return a GraphQL error indistinguishable from any other failure.
        $users = $this->graphql(
            'query ($id: String!) { users(filters: { eq: { field: "user_id", value: $id } }) { id email } }',
            ['id' => $id],
        )['users'];

        return $users[0] ?? null;
    }

    public function createUser(string $id, string $email, string $displayName): void
    {
        $this->graphql(
            'mutation ($user: CreateUserInput!) { createUser(user: $user) { id } }',
            ['user' => ['id' => $id, 'email' => $email, 'displayName' => $displayName]],
        );
    }

    public function addUserToGroup(string $id, string $groupName): void
    {
        $this->graphql(
            'mutation ($userId: String!, $groupId: Int!) { addUserToGroup(userId: $userId, groupId: $groupId) { ok } }',
            ['userId' => $id, 'groupId' => $this->groupId($groupName)],
        );
    }

    /**
     * Set a Member's password with LDAP Password Modify (RFC 3062), bound as the
     * service account. The password never enters an exception message.
     */
    public function setPassword(string $id, #[SensitiveParameter] string $password): void
    {
        $ldap = app(LdapConnection::class);

        if (! $ldap->bind($this->dn(config('services.lldap.username')), config('services.lldap.password'))) {
            throw new LldapException("LLDAP LDAP bind failed: {$ldap->error()}");
        }

        if (! $ldap->exopPasswd($this->dn($id), $password)) {
            throw new LldapException("LLDAP could not set the password for '{$id}': {$ldap->error()}");
        }
    }

    private function dn(string $id): string
    {
        return 'uid='.ldap_escape($id, flags: LDAP_ESCAPE_DN).',ou=people,'.config('services.lldap.base_dn');
    }

    /**
     * Group ids are integers assigned per installation, so resolve by name every time.
     */
    private function groupId(string $name): int
    {
        $groups = $this->graphql('query { groups { id displayName } }')['groups'];

        $group = array_find($groups, fn (array $group): bool => $group['displayName'] === $name);

        return $group['id'] ?? throw new LldapException("LLDAP group '{$name}' does not exist.");
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed> the `data` object of the response
     */
    private function graphql(string $query, array $variables = []): array
    {
        $response = $this->send(
            fn () => Http::withToken($this->token())
                ->post($this->url('/api/graphql'), ['query' => $query, 'variables' => $variables]),
        );

        // GraphQL reports failures in-band with a 200, so the HTTP check alone is not enough.
        if ($errors = $response->json('errors')) {
            throw new LldapException(implode('; ', array_column($errors, 'message')));
        }

        return $response->json('data');
    }

    private function token(): string
    {
        $response = $this->send(
            fn () => Http::post($this->url('/auth/simple/login'), [
                'username' => config('services.lldap.username'),
                'password' => config('services.lldap.password'),
            ]),
        );

        return $response->json('token');
    }

    /**
     * Fold both ways an HTTP call fails (no connection, non-2xx) into one exception type.
     *
     * @param  callable(): Response  $request
     */
    private function send(callable $request): Response
    {
        try {
            $response = $request();
        } catch (ConnectionException $e) {
            throw new LldapException("LLDAP unreachable: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new LldapException("LLDAP responded {$response->status()}: {$response->body()}");
        }

        return $response;
    }

    private function url(string $path): string
    {
        return rtrim(config('services.lldap.base_url'), '/').$path;
    }
}
