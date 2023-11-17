<?php

class seven {
    protected ?string $account = null;
    protected bool $accountActive = false;
    protected ?string $accountBody;
    protected bool $active = false;
    protected ?string $apiKey = null;
    protected ?string $contact = null;
    protected ?string $lead = null;
    protected bool $leadActive = false;
    protected ?string $leadBody;
    protected ?string $number;
    /**
     * @var Contact|Lead|Account|null $relation
     */
    protected $relation = null;
    protected ?string $sender;
    protected bool $templateActive = false;
    protected ?string $templateBody;

    private bool $isDev;

    public function __construct() {
        global $sugar_config;

        $this->isDev = true === ($sugar_config['developerMode'] ?? false);

        $this->setAccountActive($sugar_config['seven_account_active'] ?? false);
        $this->setAccountBody($sugar_config['seven_account_body'] ?? '');
        $this->setActive($sugar_config['seven_active'] ?? false);
        $this->setApiKey($sugar_config['seven_api_key'] ?? '');
        $this->setLeadActive($sugar_config['seven_lead_active'] ?? false);
        $this->setLeadBody($sugar_config['seven_lead_body'] ?? '');
        $this->setSender($sugar_config['seven_sender'] ?? '');
        $this->setTemplateActive($sugar_config['seven_template_active'] ?? false);
        $this->setTemplateBody($sugar_config['seven_template_body'] ?? '');

        if ($this->isDev) openlog('seven', LOG_NDELAY | LOG_PID, LOG_LOCAL0);
    }

    public function __destruct() {
        if ($this->isDev) closelog();
    }

    public function getTemplateActive(): bool {
        return $this->templateActive;
    }

    public function setTemplateActive(string $templateActive): self {
        $this->templateActive = 'yes' === $templateActive;
        return $this;
    }

    public function getTemplateBody(): ?string {
        return $this->templateBody;
    }

    public function setTemplateBody(string $templateBody): self {
        $this->templateBody = $templateBody;
        return $this;
    }

    public function getAccountActive(): bool {
        return $this->accountActive;
    }

    public function setAccountActive(string $accountActive): self {
        $this->accountActive = 'yes' === $accountActive;
        return $this;
    }

    public function getAccountBody(): ?string {
        return $this->accountBody;
    }

    public function setAccountBody(string $accountBody): self {
        $this->accountBody = $accountBody;
        return $this;
    }

    public function getLeadActive(): bool {
        return $this->leadActive;
    }

    public function setLeadActive(string $leadActive): self {
        $this->leadActive = 'yes' === $leadActive;
        return $this;
    }

    public function getLeadBody(): ?string {
        return $this->leadBody;
    }

    public function setLeadBody(string $leadBody): self {
        $this->leadBody = $leadBody;
        return $this;
    }

    public function sendSMS(): array {
        $to = preg_replace('~\D~', '', $this->getNumber());
        return $this->apiCall($this->getSender(), $_POST['message'], $to);
    }

    public function apiCall(?string $from, string $text, string $to): array {
        if (!$this->getActive()) return [null, null];

        $curlOpts = [
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-type: application/json',
                'SentWith: SuiteCRM',
                'X-Api-Key: ' . $this->getApiKey(),
            ],
            CURLOPT_POSTFIELDS => json_encode(compact('from', 'text', 'to')),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 7500,
        ];
        $curl = curl_init('https://gateway.seven.io/api/sms');
        curl_setopt_array($curl, $curlOpts);
        $response = curl_exec($curl);
        curl_close($curl);

        /** @var seven_sms $smsBean */
        $smsBean = BeanFactory::newBean('seven_sms');
        $smsBean->recipient = $to;
        $smsBean->sender = $from;
        $smsBean->text = $text;
        if ($this->relation instanceof Contact) $smsBean->contact_id = $this->relation->id;
        elseif ($this->relation instanceof Lead) $smsBean->lead_id = $this->relation->id;
        elseif ($this->relation instanceof Account) $smsBean->account_id = $this->relation->id;

        $smsBean->save();

        return [json_decode($response, true), $smsBean->toArray()];
    }

    public function getActive(): bool {
        return $this->active;
    }

    public function setActive(string $active): self {
        $this->active = 'yes' === $active;
        return $this;
    }

    public function getApiKey(): ?string {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getSender(): ?string {
        return $this->sender;
    }

    public function setSender($sender): self {
        $this->sender = $sender;
        return $this;
    }

    public function getNumber(): ?string {
        return $this->number;
    }

    public function setNumber(string $number): self {
        $this->number = $number;
        return $this;
    }

    public function getContactBean() {
        return $this->contact ? BeanFactory::getBean('Contacts', $this->contact) : null;
    }

    public function getLeadBean() {
        return $this->lead ? BeanFactory::getBean('Leads', $this->lead) : null;
    }

    public function getContact(): ?string {
        return $this->contact;
    }

    public function setContact(Contact $contact): self {
        $this->contact = $contact;
        return $this;
    }

    public function getLead(): ?string {
        return $this->lead;
    }

    public function setLead(Lead $lead): self {
        $this->lead = $lead;
        return $this;
    }

    public function getRelation() {
        return $this->relation;
    }

    public function setRelation($relation): self {
        $this->relation = $relation;
        return $this;
    }
}
