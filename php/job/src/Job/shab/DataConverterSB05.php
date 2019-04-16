<?php

require_once(__DIR__ . '/DataConverterSB.php');

/**
 * Class DataConverterSB05
 * SB05 = Bereinigung des Eigentumsvorbehaltsregisters
 */
class DataConverterSB05 extends DataConverterSB
{
    protected $linkDefinition_ = '//publication/meta/subRubric[text()="SB05"]/../../@ref';
    protected $topDefinition_ = '/root/*[name()="SB05:publication"]';
    protected $placeholderDefinition_ = [
        '' => [
            'cantonName'                           => '/meta/cantons',
            'pubDate'                              => '/meta/publicationDate',
            'subject'                              => '/meta/title/de',
    
            'arrestOrderNb'                        => '/content/orderNo',
            'arrestOrderDate'                      => '/content/orderDate',
            'dateOption'                           => '/content/dateOption',
            'deadline'                             => '/content/entryDeadline',
            'daysAfterPublication'                 => '/content/daysAfterPublication',

            'legalRemedy'                          => '/meta/legalRemedy',
            'additionalLegalRemedy'                => '/content/additionalLegalRemedy',
    
            'registrationOffice'                   => '/content/registrationOffice',
            'remark'                               => '/content/remarks',
        ]
    ];
    protected $neededAddresses_ = [
    ];

    public function getText()
    {
        return $this->cleanText(
            $this->implementField('subject', '', "\n")
            . $this->implementField('cantonName', 'Kanton: ', "\n")
            . $this->implementField('pubDate', 'Publikationsdatum SHAB: ', "\n")
            . $this->implementField('dateOption', 'Zu löschende eingetragene Eigentumsvorbehalte vor: ', "\n")
            . $this->notice("\r\nRechtliche Hinweise: \n", "\n")
            . $this->implementField('daysAfterPublication', "\r\nFrist: ", " Tage\n")
            . $this->implementField('deadline', "Ablauf der frist: ", "\n")
            . $this->implementField('remark', "\r\nBemerkung: \n", "\n")
            . $this->implementField('registrationOffice', "\r\nAnmeldestelle für Forderungen, Einsprachen oder Rekurse:\n", ".\n")
        );
    }

}
