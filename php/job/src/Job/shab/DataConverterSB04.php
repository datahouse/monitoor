<?php

require_once(__DIR__ . '/DataConverterSB.php');

/**
 * Class DataConverterSB04
 * SB04 = Pfändungsanzeigen und -urkunden
 */
class DataConverterSB04 extends DataConverterSB
{
    protected $linkDefinition_ = '//publication/meta/subRubric[text()="SB04"]/../../@ref';
    protected $topDefinition_ = '/root/*[name()="SB04:publication"]';
    protected $placeholderDefinition_ = [
        '' => [
            'cantonName'                           => '/meta/cantons',
            'pubDate'                              => '/meta/publicationDate',
            'subject'                              => '/meta/title/de',
    
            'arrestOrderNb'                        => '/content/orderNo',
            'arrestOrderDate'                      => '/content/orderDate',
    
            'claimChf'                             => '/content/claims/claimAmount', /* this can appear multiple times */
            'claimInterest'                        => '/content/claims/claimInterest', /* this can appear multiple times */
            'claimDate'                            => '/content/claims/claimDate', /* this can appear multiple times */
    
            'additionalCosts'                      => '/content/otherCosts', # Zusätzliche Kosten
            'legalRemedy'                          => '/meta/legalRemedy',
            'additionalLegalRemedy'                => '/content/additionalLegalRemedy',
            'remark'                               => '/content/remarks',
    
            'registrationOffice'                   => '/content/registrationOffice',
    
            'arrestObjects'                        => '/content/arrestObjects',
            'arrestAuthority'                      => '/content/arrestAuthority', #Arrestbehörde
            'arrestReason'                         => '/content/arrestReason', #ForderungeGrund
            'arrestDeed'                           => '/content/arrestDeed', #Urkunde
        ]
    ];
    protected $neededAddresses_ = [
        'schuldner' => 'debtor',
        'creditor' => 'creditor',
        'representative' => 'representativ',
    ];

    public function getText()
    {
        return $this->cleanText(
            $this->implementField('subject', '', "\n")
            . $this->implementField('cantonName', 'Kanton: ', "\n")
            . $this->implementField('pubDate', 'Publikationsdatum SHAB: ', "\n")
            . $this->address('schuldner', 'Schuldner: ', "\n")
            . $this->address('creditor', 'Gläubiger: ', "\n")
            . $this->address('representative', 'Vertreter: ', "\n")
            . $this->arrestOrder("\n")
            . $this->claim("\n") 

            . $this->implementField('additionalCosts', "\r\nZusätzliche Kosten: \n", ".\n")
            . $this->implementField('claimDocument', "\r\nForderungsurkunde/-Grund: \n", ".\n")
            . $this->implementField('claimReason', "\r\nArrestgrund: \n", ".\n")
            . $this->implementField('arrestDocument', 'Arresturkunde: ', ".\n")
            . $this->notice("\r\nRechtliche Hinweise: \n", "\n")
            . $this->implementField('registrationOffice', "\r\nAnmeldestelle für Forderungen, Einsprachen oder Rekurse:\n", ".\n")
            . $this->implementField('remark', "\r\nBemerkung: \n", "\n")
        );
    }

    private function arrestOrder($postIfNotEmpty)
    {
        if ($this->checkAllNull('arrestOrderNb','arrestOrderDate')) {
            return '';
        }
        $ret = $this->implementField('arrestOrderNb', '', ' ') . $this->implementField('arrestOrderDate', 'vom ', ' ');
        return  'Schuldbetreibung/en Nr. ' . trim($ret, " \n") . $postIfNotEmpty;
    }

}
