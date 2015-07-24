<?php

namespace Bpi\Sdk;

/**
 * Class ResponseStatus check status of response got from WS.
 */
class ResponseStatus
{
    /**
     * @var string status of response.
     */
    protected $status;

    /**
     * Initiate object.
     *
     * @param int $status_code
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($status_code)
    {
        $this->status = (string) $status_code;

        if ($this->status <= 0) {
            throw new \InvalidArgumentException('Incorrect HTTP status code ['.$status_code.']');
        }
    }

    /**
     * Convert current status to string.
     *
     * @return string with current status
     */
    public function __toString()
    {
        return (string) $this->status;
    }

    /**
     * Return status code of finished request.
     *
     * @return string current status code
     */
    public function getCode()
    {
        return $this->status;
    }

    /**
     * Check if request was successful.
     *
     * @return bool if status successful
     */
    public function isSuccess()
    {
        return $this->status[0] == 2;
    }

    /**
     * Check if error code is client error.
     *
     * @return bool if status code is client error
     */
    public function isClientError()
    {
        return $this->status[0] == 4;
    }

    /**
     * Check if error code is server error.
     *
     * @return bool if status code is server error
     */
    public function isServerError()
    {
        return $this->status[0] == 5;
    }

    /**
     * Check if error response.
     *
     * @return bool if status code is error
     */
    public function isError()
    {
        return $this->isClientError() || $this->isServerError();
    }
}
