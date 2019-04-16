<?php
require_once(__DIR__ . '/DataConverterSB.php');

/**
 * Class DataConverterSB01
 * SB01 = Betreibungsamtliche Grundstücksteigerung
 */
class DataConverterSB01 extends DataConverterSB
{
    protected $linkDefinition_ = '//publication/meta/subRubric[text()="SB01"]/../../@ref';
    protected $topDefinition_ = '/root/*[name()="SB01:publication"]';
    protected $placeholderDefinition_ = [
        '' => [
            'cantonName'                          => '/meta/cantons',
            'pubDate'                             => '/meta/publicationDate',
            'subject'                             => '/meta/title/de',
            'expirationDate'                      => '/meta/expirationDate',

            'auctionDate'                         => '/content/auction/date',
            'auctionTime'                         => '/content/auction/time',
            'auctionLocation'                     => '/content/auction/location',

            'auctionObjects'                      => '/content/auctionObjects',
            'remark'                              => '/content/remarks',
            /* rechtliche hinweise */
            'legalRemedy'                         => '/meta/legalRemedy',
            'additionalLegalRemedy'               => '/content/additionalLegalRemedy',
            'locationCirculationAuthority'        => '/content/locationCirculationAuthority',

            'auctionConditionCirculationDate'     => '/content/circulation/entryDeadline',
            'auctionConditionCirculationComment'  => '/content/circulation/commentEntryDeadline',
            'auctionConditionRegistrationDate'    => '/content/registration/entryDeadline',
            'auctionConditionRegistrationComment' => '/content/registration/commentEntryDeadline',

            'registrationOffice'                  => '/content/registrationOffice',
        ]
    ];
    protected $neededAddresses_ = [
        'schuldner' => 'debtor',
    ];

    public function getText()
    {
        return $this->cleanText(
            $this->implementField('subject', '', "\n")
            . $this->implementField('cantonName', 'Kanton: ', "\n")
            . $this->implementField('pubDate', 'Publikationsdatum SHAB: ', "\n")
            . $this->address('schuldner', 'Schuldner: ', "\n")
            . $this->implementField('auctionObjects', "\r\nSteigerungsobjekte:\n", ".\n\r\n")
            . $this->auction("\n")
            . $this->notice("\r\nRechtliche Hinweise: \n", "\n")
            . $this->auctionCondition("\n")

            //. $this->implementField('locationCirculationAuthority', "\r\nAnmeldestelle für Forderungen, Einsprachen oder Rekurse:\n", "\n")
            . $this->implementField('remark', 'Bemerkung: ', "\n")
        );
    }

    private function auction($post)
    {
        if ($this->checkAllNull('auctionDate','auctionTime','auctionLocation')) {
            return '';
        }
        return 'Ort/Datum der Steigerung: '
        . $this->getField('auctionDate') . ', '
        . $this->getField('auctionTime') . 'h, '
        . $this->getField('auctionLocation') . $post;
    }


    private function auctionCondition($post)
    {
        $ret = '';
        /* Eingabefrist */
        if (!$this->checkAllNull('auctionConditionCirculationDate','auctionConditionCirculationComment')) {
            $ret .= 'Eingabefrist: '
                . $this->implementField('auctionConditionCirculationDate', '', ', ')
                . $this->implementField('auctionConditionCirculationComment', '', '');
            $ret .= "\n";
        }
    
        if (!$this->checkAllNull('auctionConditionRegistrationDate','auctionConditionRegistrationComment')) {
            $ret .= 'Steigerungsbedingungen und Lastenverzeichnis liegen auf '
                . $this->implementField('auctionConditionRegistrationDate', 'ab dem ', ', ') 
                . $this->implementField('auctionConditionRegistrationComment', '', '');
            $ret .= "\n";
        }
        /* Anmeldestelle für Forderungen, Einsprachen oder Rekurse */
        $ret .= $this->implementField('registrationOffice', 'Anmeldestelle für Forderungen, Einsprachen oder Rekurse: ', ".");

        return $ret == '' ? '' : $ret . $post;
    }

}
