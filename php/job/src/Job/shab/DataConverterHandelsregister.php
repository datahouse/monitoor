<?php
require_once(__DIR__ . '/../common/DataConverter.php');

class DataConverterHandelsregister extends DataConverter
{
    protected $linkDefinition_ = '//publication/meta/rubric[text()="HR"]/../../@ref';
    protected $topDefinition_ = '/root/*[local-name() = "publication"]';
    protected $placeholderDefinition_ = [
        '' => [
            'cantonName'                          => '/meta/cantons',
            'pubDate'                             => '/meta/publicationDate',
            'subject'                             => '/meta/title/de',

            'pubContent'                          => '/content/publicationText',

            'firmUID'                             => '/content/commonsActual/company/uid',
            'actualPurpose'                       => '/content/commonsActual/purpose',
            'newPurpose'                          => '/content/newActual/purpose',
            'previousDate'                        => '/content/lastFosc/lastFoscDate',
            'previousNr'                          => '/content/lastFosc/lastFoscNumber',

            'legalRemedy'                         => '/meta/legalRemedy',
            'additionalLegalRemedy'               => '/content/additionalLegalRemedy',
            'locationCirculationAuthority'        => '/content/locationCirculationAuthority',

            'journalNr'                           => '/content/journalNumber',
            'journalDate'                         => '/content/journalDate',
            'senderOffice'                        => '/content/senderOffice/officeName',
        ]
    ];
    protected $neededAddresses_ = [
        'actual' => 'commonsActual',
        'new'    => 'commonsNew',
    ];

    public function get_monitor_id()
    {
        return MON_ID_HANDELSREGISTER;
    }

    public function getTimeStamp()
    {
        return $this->cleanTimestamp(strip_tags($this->getField('logDate')));
    }

    public function getText()
    {
        return $this->cleanText(
            $this->implementField('subject', '', "\n")
            . $this->address('actual', '', "\n")
            . $this->implementField('cantonName', 'Kanton: ', "\n")
            . $this->implementField('pubDate', 'Publikationsdatum SHAB: ', "\n")
            . $this->implementField('firmUID', 'FirmUID: ', "\n")

            . $this->implementField('newPurpose', "\r\n", "\n")
            . $this->implementField('actualPurpose', "\r\nBisher:\n", "\n")
            . $this->implementField('pubContent', "\r\n", "\n\r\n")

            . $this->previous("\n")
            . $this->notice("\r\nBemerkung\n", "\n\r\n")
            . $this->tagesRegister("\n")
            . $this->implementField('senderOffice', 'Verantwortliches Amt: ', '')
        );
    }

    private function previous($postIfNotEmpty)
    {
        if ($this->checkAllNull('previousDate', 'previousNr')) {
            return '';
        }
        return 'Datum der Veröffentlichung im SHAB: ' . $this->getField('previousDate')
            . ' Ausgabe Nr. ' . $this->getField('previousNr') . $postIfNotEmpty;
    }

    private function tagesRegister($postIfNotEmpty)
    {
        if ($this->checkAllNull('journalDate', 'journalNumber')) {
            return '';
        }
        return 'Tagesregister-Nr. ' . $this->getField('journalNumber') . ' vom ' . $this->getField('journalDate') . $postIfNotEmpty;
    }
    
}
