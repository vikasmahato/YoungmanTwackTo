<?php


namespace App\Repositories\YoungmanManufacturing;

use DB;
use App\User;

class TwackToOdooRepository implements IOdooRepository
{

    const TWAK_TO = "TWAK_TO";
    public function recordNotProcessed($uniqueId)
    {
        $data = DB::select("select * from odoo_twakto_lead_map where email_unique_id = ?",  [$uniqueId]);
        return count($data) == 0;
    }

    public function markRecordAsProcessed($uniqueId, $odooLead, $destination, $sourceRecord, $destinationRecord)
    {
        DB::insert("INSERT INTO `odoo_twakto_lead_map`(`email_unique_id`, `remote_lead_id`, `lead_destination`, `twak_to_lead`, `remote_lead`) 
                            VALUES (?,?,?,?,?)", [$uniqueId , $odooLead, $destination, $sourceRecord, $destinationRecord]);
    }

    public function saveAccessToken($token)
    {
        // TODO: Implement saveAccessToken() method.
    }

    public function getAccessToken()
    {
        // TODO: Implement getAccessToken() method.
    }

    public function getAccessTokenFromCache()
    {
        // TODO: Implement getAccessTokenFromCache() method.
    }

    public function refreshAccessToken()
    {
        // TODO: Implement refreshAccessToken() method.
    }

    public function getUsersForLeadAssignemnt()
    {
        $users = DB::select("select users.*,third_party_leads_assign.odoo_user_id from users, user_assign_lead_src_rel, third_party_leads_assign where users.id = third_party_leads_assign.user_id and third_party_leads_assign.id = user_assign_lead_src_rel.user_assign_id and user_assign_lead_src_rel.lead_src = ?", [SELF::TWAK_TO]);
        return User::hydrate($users);
    }
}
