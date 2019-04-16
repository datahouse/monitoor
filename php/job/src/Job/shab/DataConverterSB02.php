<?php
require_once(__DIR__ . '/DataConverterSB.php');

/**
 * Class DataConverterSB02
 * SB02 = Zahlungsbefehle
 */
class DataConverterSB02 extends DataConverterSB
{
    protected $linkDefinition_ = '//publication/meta/subRubric[text()="SB02"]/../../@ref';
    protected $topDefinition_ = '/root/*[name()="SB02:publication"]';
    protected $placeholderDefinition_ = [
        '' => [
            'cantonName'                           => '/meta/cantons',
            'pubDate'                              => '/meta/publicationDate',
            'subject'                              => '/meta/title/de',

            'payDemandNb'                          => '/content/orderNo',
            'payDemandDate'                        => '/content/orderDate',
            'orderClass'                           => '/content/orderClass/selectType',

            'claimChf'                             => '/content/claims/claimAmount', /* this can appear multiple times */
            'claimInterest'                        => '/content/claims/claimInterest', /* this can appear multiple times */
            'claimDate'                            => '/content/claims/claimDate', /* this can appear multiple times */
            'claimDescription'                     => '/content/claims/description', /* this can appear multiple times */
    
            'additionalCosts'                      => '/content/otherCosts',
            'claimReason'                          => '/content/claimReason',
            'legalRemedy'                          => '/meta/legalRemedy',
            'additionalLegalRemedy'                => '/content/additionalLegalRemedy',
            'remark'                               => '/content/remarks',
    
            'registrationFirmName'                 => '/meta/registrationOffice/displayName',
            'registrationFirmStreet'               => '/meta/registrationOffice/street',
            'registrationFirmHouseNumber'          => '/meta/registrationOffice/streetNumber',
            'registrationFirmZIP'                  => '/meta/registrationOffice/swissZipCode',
            'registrationFirmCity'                 => '/meta/registrationOffice/town',
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
            . $this->implementField('subject', '', "\n")
            . $this->implementField('cantonName', 'Kanton: ', "\n")
            . $this->implementField('pubDate', 'Publikationsdatum SHAB: ', "\n")
            . $this->payDemand("\n")
            . $this->address('schuldner', 'Schuldner: ', "\n")
            . $this->address('creditor', 'Gläubiger: ', "\n")
            . $this->address('representative', 'Vertreter: ', "\n")
            . $this->OrderType("Art der Schuldbetreibung: ", "\n")
            . $this->claim("\n")
            . $this->implementField('additionalCosts', "\r\nZusätzliche Kosten:\n", "\n")
            . $this->implementField('claimReason', "\r\nForderungsgrund:\n", "\n")
            . $this->notice("\r\nRechtliche Hinweise: \n", "\n")
            . $this->implementField('remark', 'Bemerkung: ', "\n")
            . $this->address('registration', "\r\nAnmeldestelle für Forderungen, Einsprachen oder Rekurse:\n", "\n")
        );
    }

    private function orderType($preIfNotEmpty, $postIfNotEmpty)
    {
        $type = $this->getField('orderClass');
        $txt = '';
        switch ($type) {
        case 'ordinaryProcedure':
            $txt = 'Ordentliches Verfahren';
            break;
        case 'pledge':
            $txt = 'Betreibung auf Verwertung eines Faustpfandes';
            break;
        case 'mortgages':
            $txt = 'Betreibung auf Verwertung eines Grundpfandes';
            break;
        case 'securityDeposit':
            $txt = 'Betreibung auf Sicherheitsleistung';
            break;
        default:
            $txt = 'Typ; ' . $type;
            $headers = "Content-type: text/plain; charset=utf-8\r\n";
            $headers .= 'From: ' . EXCEPTION_FROM . "\r\n";
            mail(EXCEPTION_TO , 'MON / Warning: unknown order Type encountered: ' . $type, $txt, $headers);
            break;
        }
        return $txt = '' ? '' : ($preIfNotEmpty . $txt . $postIfNotEmpty);
    }

}
