<?php

use App\Exceptions\LldapException;
use App\Services\LdapConnection;
use App\Services\Lldap;

beforeEach(function (): void {
    config([
        'services.lldap.username' => 'solamnia-portal-svc',
        'services.lldap.password' => 'svc-secret',
        'services.lldap.base_dn' => 'dc=solamnia,dc=tv',
    ]);
});

it('binds as the service account and sets the password on the member DN', function (): void {
    $this->mock(LdapConnection::class, function ($mock): void {
        $mock->shouldReceive('bind')
            ->once()
            ->ordered()
            ->with('uid=solamnia-portal-svc,ou=people,dc=solamnia,dc=tv', 'svc-secret')
            ->andReturnTrue();
        $mock->shouldReceive('exopPasswd')
            ->once()
            ->ordered()
            ->with('uid=brightblade,ou=people,dc=solamnia,dc=tv', 'correct horse battery')
            ->andReturnTrue();
    });

    app(Lldap::class)->setPassword('brightblade', 'correct horse battery');
});

it('raises LldapException without either password when the bind fails', function (): void {
    $this->mock(LdapConnection::class, function ($mock): void {
        $mock->shouldReceive('bind')->andReturnFalse();
        $mock->shouldReceive('error')->andReturn('Invalid credentials');
        $mock->shouldNotReceive('exopPasswd');
    });

    expect(fn () => app(Lldap::class)->setPassword('brightblade', 'correct horse battery'))
        ->toThrow(function (LldapException $e): void {
            expect($e->getMessage())
                ->toContain('Invalid credentials')
                ->not->toContain('svc-secret')
                ->not->toContain('correct horse battery');
        });
});

it('raises LldapException without the password when the modify fails', function (): void {
    $this->mock(LdapConnection::class, function ($mock): void {
        $mock->shouldReceive('bind')->andReturnTrue();
        $mock->shouldReceive('exopPasswd')->andReturnFalse();
        $mock->shouldReceive('error')->andReturn('Insufficient access');
    });

    expect(fn () => app(Lldap::class)->setPassword('brightblade', 'correct horse battery'))
        ->toThrow(function (LldapException $e): void {
            expect($e->getMessage())
                ->toContain('brightblade')
                ->toContain('Insufficient access')
                ->not->toContain('correct horse battery');
        });
});

it('raises LldapException when the LDAP URL is not configured', function (): void {
    config(['services.lldap.ldap_url' => null]);

    expect(fn () => app(Lldap::class)->setPassword('brightblade', 'correct horse battery'))
        ->toThrow(LldapException::class, 'LLDAP_LDAP_URL is not set.');
});
