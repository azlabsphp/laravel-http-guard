<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\HttpGuard\OAuth2;

use DateTimeInterface;
use Drewlabs\Contracts\OAuth\Token;
use Drewlabs\Core\Helpers\DateTime;
use Drewlabs\HttpGuard\Facades\OAuth2Provider;
use Override;

final class AccessToken implements Token
{
    /** @var string */
    private $value;

    /** @var string[] */
    private $scopes = [];

    /** @var \DateTimeInterface|null */
    private $expiresAt = null;

    /**
     * create access token class instance
     * 
     * @param string $value 
     * @param array $scopes 
     * @param null|DateTimeInterface $expiresAt 
     * @return void 
     */
    public function __construct(string $value, array $scopes = [], ?\DateTimeInterface $expiresAt = null)
    {
        $this->value = $value;
        $this->scopes = $scopes;
        $this->expiresAt = $expiresAt;
    }


    /**
     * checks whether the token has expire.
     *
     * @return bool
     */
    public function expires()
    {
        if (is_null($expiresAt = $this->expiresAt())) {
            return true;
        }

        return !DateTime::isfuture($expiresAt);
    }

    /**
     * returns the expiration date of the token.
     *
     * @return ?DateTimeInterface
     */
    public function expiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }


    #[Override]
    public function revoke()
    {
        return OAuth2Provider::revokeOAuthToken($this->value);
    }

    #[Override]
    public function transient()
    {
        return false;
    }

    #[Override]
    public function can($ability)
    {
        return \in_array('*', $this->scopes, true) || \array_key_exists($ability, array_flip($this->scopes));
    }

    #[Override]
    public function cant($ability)
    {
        return !$this->can($ability);
    }

}