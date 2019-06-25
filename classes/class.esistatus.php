<?php
require_once('config.php');

use Swagger\Client\Configuration;
use Swagger\Client\ApiException;
use Swagger\Client\Api\StatusApi;

require_once('vendor/autoload.php');
require_once('classes/esi/autoload.php');


class ESISTATUS extends ESIAPI
{
        protected $log;

        public function __construct() {
            $this->log = new ESILOG('log/esi.log');
            parent::__construct();
        }

        public function getServerStatus() {
            $statusapi = $this->getApi('Status');
            try {
                $response = json_decode($statusapi->getStatus('tranquility'), true);
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Could not fetch Server status: '.$e->getMessage().PHP_EOL;
                $this->log->error($this->message);
                return false;
            }
            return $response;
        }
}
