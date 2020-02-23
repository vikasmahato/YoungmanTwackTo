<?php


namespace App\Repositories\YoungmanManufacturing;


interface IOdooRepository
{
    public function recordNotProcessed($uniqueId);
    public function markRecordAsProcessed($uniqueId, $odooLead, $destination, $sourceRecord, $destinationRecord);

    public function saveAccessToken($token);
    public function getAccessToken();
    public function getAccessTokenFromCache();
    public function refreshAccessToken();

    public function getUsersForLeadAssignemnt();
}