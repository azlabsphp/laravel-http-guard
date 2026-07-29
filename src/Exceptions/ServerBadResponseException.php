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

namespace Drewlabs\HttpGuard\Exceptions;

use Drewlabs\Curl\REST\Contracts\ResponseInterface;
use Psr\Http\Message\ResponseInterface as BaseResponseInterface;

class ServerBadResponseException extends \Exception
{

    /** @var ResponseInterface|BaseResponseInterface */
    private $response;
    
    /**
     * 
     * @param ResponseInterface|BaseResponseInterface $response 
     * @return void 
     */
    public function __construct($response)
    {
        return parent::__construct($response->getReasonPhrase(), $response->getStatus(), null);

        $this->reponse = $response;
    }

    /**
     * returns server response body
     * 
     * @return string 
     */
    public function getBody() 
    {
        return strval($this->response->getBody());
    }


    /**
     * returns http code of the server response
     * 
     * @return int 
     */
    public function getStatusCode()
    {
        return $this->response->getStatus();
    }
}
