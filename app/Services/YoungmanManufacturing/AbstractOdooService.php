<?php


namespace App\Services\YoungmanManufacturing;


use App\Repositories\YoungmanManufacturing\IOdooRepository;
use App\Services\LeadsService;
use App\Services\OdooService;
use GuzzleHttp\Client;

abstract class AbstractOdooService
{
    protected $odooService;
    protected $leadService;
    protected $client;
    protected $odooRepository;
    protected $token;

    function __construct($baseUri, IOdooRepository $odooRepository)
    {
        $this->odooService = new OdooService();
        $this->leadService = new LeadsService();
        $this->odooRepository = $odooRepository;
        $this->client = new Client([
            'base_uri' => $baseUri,
            'timeout'  => 100.0,
        ]);
    }

    abstract function queryDataFromSource();
    abstract function processSourceRecord($data, $uniqueId);
    abstract function createQLead($data, $uniqueId);
    abstract function createOdooLead($data, $uniqueId);
    abstract function transformToOdooRequest($data);

    abstract function saveApiKey($key);

}