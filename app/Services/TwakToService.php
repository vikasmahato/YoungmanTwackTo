<?php


namespace App\Services;

use App\Helper\ValidationUtils;
use App\Leads;
use App\Repositories\YoungmanManufacturing\TwackToOdooRepository;
use App\Services\YoungmanManufacturing\AbstractOdooService;
use DOMDocument;
use DB;
class TwakToService extends AbstractOdooService
{
    protected $imap_stream;
    protected $odooUsers;
    protected $currUserIndex;
    private $supervisor_id;
    private $rental_leads_user;


    function __construct()
    {
        parent::__construct("", new TwackToOdooRepository());
        $host = "{outlook.office365.com:993/imap/ssl/authuser=". env('MAIL_USERNAME')."}";
        $this->imap_stream = imap_open($host, env('MAIL_USERNAME'),env('MAIL_PASSWORD')) or die('<div class="alert alert-danger alert-dismissable">Cannot connect to outlook.com: ' . imap_last_error().'</div>');
        $this->currUserIndex = -1;
    }

    function queryDataFromSource()
    {
        $array = imap_search($this->imap_stream,'FROM "sgupta@youngman.co.in"');

        $this->supervisor_id = DB::select("select user_id from third_party_leads_assign where user_src = 'BETA' and lead_type = 'ALL'")[0]->user_id;
        $this->rental_leads_user = DB::select("select user_id from third_party_leads_assign where user_src = 'BETA' and lead_type = 'RENTAL'")[0]->user_id;
        $this->odooUsers = $this->odooRepository->getUsersForLeadAssignemnt();

        foreach($array as $email) {
            $message = imap_fetchbody($this->imap_stream,$email,1);
            $doc = new DOMDocument();
            $doc->loadHTML( $message );
            $tags = $doc->getElementsByTagName('table');

            $this->processSourceRecord($tags[1]->nodeValue, $email);
        }
    }

    function processSourceRecord($data, $uniqueId)
    {
        if($this->odooRepository->recordNotProcessed($uniqueId)) {
            $data = $this->sanitizeRecord($data);
            if ($data['type'] == "RENTAL") {
                $this->createQLead($data, $uniqueId);
            } else {
                $this->createOdooLead($data, $uniqueId);
            }
        }
    }

    function createQLead($data, $uniqueId)
    {
        $action = " Sender- ".$data['email']." ".$data['number'];
        $remarks = $data['description'];
        $qLead = [
            "site_id" => null,
            'status' => Leads::$QUOTATION,
            'action' => $action,
            'due_date' => date("Y-m-d"),
            'assigned_to' => $this->rental_leads_user,
            'source' => 'TWAK_TO',
            'created_by' => $this->supervisor_id,
            'remarks' => $remarks,
            'prospective_customer_company'=>null,
            'prospective_contact_name'=>null,
            'prospective_contact_email'=>$data['email'],
            'prospective_contact_phone'=>$data['email']
        ];

        $id = $this->leadService->store($qLead);
        $this->odooRepository->markRecordAsProcessed($uniqueId, $id, 'BETA',  json_encode($data),json_encode($qLead));

    }

    function createOdooLead($data, $uniqueId)
    {
        $odooLeadRaw = $this->transformToOdooRequest($data);
        $odooLead = $this->odooService->create('crm.lead', $odooLeadRaw);
        $this->odooRepository->markRecordAsProcessed($uniqueId, $odooLead,'ODOO', json_encode($data), json_encode($odooLeadRaw) );
    }

    function transformToOdooRequest($data)
    {
        $odooLead = [
            "active" => true,
            "team_id" => 1, //"Sales"],
            "name" => $data['email'],
            "description" => $data['description'],
            "type" => "lead",
            "priority" => "0",
            "stage_id" => 1, //"New"
            "user_id" => $this->odooUsers[$this->getNextUserIndex()]->odoo_user_id,
            "country_id" => 104,
            "mobile" => $data['number'],
            "display_name" => $data['email'],
        ];

        return $odooLead;
    }

    function saveApiKey($key)
    {
        // TODO: Implement saveApiKey() method.
    }

    function sanitizeRecord($data) {
        $sanitizedData = array();
        $sanitizedData['name'] = null;
        $sanitizedData['email'] = ValidationUtils::extractEmail($data);
        $sanitizedData['number'] = ValidationUtils::extractMobNumber($data);
        $sanitizedData['description'] = $data;
        $sanitizedData['type'] = ValidationUtils::contains(": Rental", $data) ? "RENTAL" : "PURCHASE";
        return $sanitizedData;
    }

    function getNextUserIndex() {
        $this->currUserIndex++;
        return ($this->currUserIndex) % count($this->odooUsers);
    }
}
