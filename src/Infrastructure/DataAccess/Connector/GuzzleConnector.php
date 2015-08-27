<?php

namespace Honeybee\Infrastructure\DataAccess\Connector;

use Honeybee\Common\Error\RuntimeError;
use Guzzle\Http\Client;

class GuzzleConnector extends Connector
{
    public function connect()
    {
        $base_uri = null;
        if ($this->config->has('transport') && $this->config->has('host') && $this->config->has('port')) {
           $base_uri = sprintf(
                '%s://%s:%s',
                $this->config->get('transport'),
                $this->config->get('host'),
                $this->config->get('port')
            );
        }

        $request_options = [];
        if ($this->config->has('auth')) {
            $auth = $this->config->get('auth');
            if (!isset($auth['username'])) {
                throw new RuntimeError('Missing required "username" setting within given auth config.');
            }
            if (!isset($auth['password'])) {
                throw new RuntimeError('Missing required "password" setting within given auth config.');
            }
            $request_options['auth'] = [ $auth['username'], $auth['password'], $auth['type'] ?: 'basic' ];
        }
        if ($this->config->has('default_headers')) {
            $request_options['headers'] = (array)$this->config->get('default_headers');
        }

        $client_options = [];
        if (!empty($request_options)) {
            $client_options['request.options'] = $request_options;
        }

        return new Client($base_uri, $client_options);
    }
}
