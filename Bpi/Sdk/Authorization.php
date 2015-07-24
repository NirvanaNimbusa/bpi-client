<?php

namespace Bpi\Sdk;

/**
 * Class Authorization prepare authorization credentials.
 */
class Authorization
{
    /**
     * @var string agency id
     */
    protected $agency_id;

    /**
     * @var string public key used to authorize on rest server
     */
    protected $public_key;

    /**
     * @var string secret key used to authorize on rest server
     */
    protected $secret_key;

    /**
     * @var string token by which client authorizes on service
     */
    protected $token;

    /**
     * @param string $agency_id
     * @param string $public_key
     * @param string $secret_key
     */
    public function __construct($agency_id, $public_key, $secret_key)
    {
        $this->agency_id = $agency_id;
        $this->public_key = $public_key;
        $this->secret_key = $secret_key;
        $this->generateToken();
    }

    /**
     * Generate authorization token.
     */
    protected function generateToken()
    {
        $this->token = crypt($this->agency_id.$this->public_key.$this->secret_key);
    }

    /**
     * Represent as HTTP Authorization string.
     * Will return value part, e.g. Authorization: <value>.
     *
     * @return string
     */
    public function toHTTPHeader()
    {
        return sprintf('BPI agency="%s", token="%s"', $this->agency_id, $this->token);
    }
}
