<?php
require_once(__DIR__ . '/DataConverterSB.php');

/**
 * Class DataConverterKK
 * KK08 = Konkursamtliche Grundstückssteigerung
 */
class DataConverterKK08 extends DataConverterSB
{
    protected $linkDefinition_ = '//publication/meta/subRubric[text()="KK08"]/../../@ref';
    protected $topDefinition_ = '/root/*[name()="KK08:publication"]';

    protected $placeholderDefinition_ = [
        '' => [
            'cantonName'                          => '/meta/cantons',
            'pubDate'                             => '/meta/publicationDate',
            'subject'                             => '/meta/title/de',

            'auctionDate'                         => '/content/auction/date',
            'auctionTime'                         => '/content/auction/time',
            'auctionLocation'                     => '/content/auction/location',
            'entryStart'                          => '/content/entryStart',
            'entryDeadline'                       => '/content/entryDeadline',

            'auctionObjects'                      => '/content/auctionObjects',
            'remark'                              => '/content/remarks',
            'informationAboutEdition'             => '/content/informationAboutEdition',

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
        //'creditor' => 'creditor',
        //'representative' => 'representativ',
    ];

    public function get_monitor_id()
    {
        return MON_ID_KONKURSE;
    }

    public function getText()
    {
        return $this->cleanText(
            $this->implementField('subject', '', "\n")
            . $this->implementField('subject', '', "\n")
            . $this->implementField('cantonName', 'Kanton: ', "\n")
            . $this->implementField('pubDate', 'Publikationsdatum SHAB: ', "\n")
            . $this->address('schuldner', 'Schuldner: ', "\n\r\n")
            . $this->implementField('auctionObjects', "\r\nSteigerungsobjekte:\n", ".\n\r\n")
            . $this->auction("\n")
            . $this->notice( "\r\nRechtliche Hinweise: \n", "\n\r\n")
            . $this->implementField('entryStart', "Beginn der Frist: ", "\n")
            . $this->implementField('entryDeadline', "Ablauf der Frist: ", "\n")
            . $this->implementField('registrationOffice', "\r\nAnmeldestelle für Forderungen, Einsprachen oder Rekurse:\n", "\n\r\n")
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

    protected function notice($preIfNotEmpty, $postIfNotEmpty)
    {
        $ret = '';
        if ($this->checkAllNull('informationAboutEdition','locationCirculationAuthority','legalRemedy','additionalLegalRemedy')) {
            return $ret;
        }
        $ret .= $this->implementField('informationAboutEdition', '', "\r\n")
            . $this->implementField('locationCirculationAuthority',  "\r\n", "\n")
            . $this->implementField('legalRemedy', "\r\n", "\n")
            . $this->implementField('additionalLegalRemedy',  "\r\n", "\n");
        return $preIfNotEmpty . trim($ret, " \n") . $postIfNotEmpty;
    }

    private function endDate()
    {
        if ($this->checkAllNull('endDate')) {
            return '';
        }
        return 'Eingabefrist: ' . $this->getField('endDate');
    }
}
