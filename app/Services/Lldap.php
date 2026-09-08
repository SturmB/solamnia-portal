<?php

namespace App\Services;

use App\Exceptions\LldapException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * The portal's provisioning surface into LLDAP over its GraphQL API, using the
 * dedicated service account (never the directory superuser). A JWT is fetched
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
