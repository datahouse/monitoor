<?php

require_once(__DIR__ . '/DataConverterSB.php');

/**
 * Class DataConverterSB06
 * SB06 = Weitere Bekannmachungen
 */
class DataConverterSB06 extends DataConverterSB
{
    protected $linkDefinition_ = '//publication/meta/subRubric[text()="SB06"]/../../@ref';
    protected $topDefinition_ = '/root/*[name()="SB06:publication"]';
    protected $placeholderDefinition_ = [
        '' => [
            'cantonName'                           => '/meta/cantons',
            'pubDate'                              => '/meta/publicationDate',
            'subject'                              => '/meta/title/de',
            'text'                                 => '/content/publication',
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
            . $this->implementField('text', "\r\n", "\n")
        );
    }

}
