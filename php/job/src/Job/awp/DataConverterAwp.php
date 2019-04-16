<?php
require_once(__DIR__ . '/../common/DataConverter.php');

class DataConverterAwp extends DataConverter
{
    protected $topDefinition_ = '/';
    protected $placeholderDefinition_ = [
        '' => [
            'dateTime' => '//NewsEnvelope/DateAndTime',
            'bodyContent'    => '//body.content',
            'headLine' => '//NewsItem/NewsComponent/NewsLines/HeadLine',
        ],
        'Value' => [
            'fullName' => '//Property[@FormalName="FullName"]',

            'telekurs' => '//Property[@FormalName="Telekurs"]',
            'handelsRegisterNr' => '//Property[@FormalName="CHNr"]',
            'ticker' => '//Property[@FormalName="Ticker"]',

            'branche' => '//Property[@FormalName="Industry"]',
            'isin' => '//Property[@FormalName="ISIN"]',
            'rubrik' => '//Property[@FormalName="Subject"]'

        ]
    ];
    protected $includeRequirement_ = '//NewsItemType[@FormalName="News"]';

    protected $replacementMap_ = [
        'type' => [
            'Background' => 'Hintergrundmeldung',
            'Current' => 'Nachricht',
            'Daybook' => 'Veranstaltungskalender',
            'Forecast' => 'Prognose',
            'Press release' => 'Pressematerial',
            'Press-digest' => 'Presseschau',
            'Summary' => 'Überblick',
            'Wrap' => 'Zusammenfassung'
        ],
        'rubrik' => [
            'AGM' => 'Generalversammlungen',
            'BCY' => 'Bankrotte, Nachlassstundungen und Konkurse',
            'DIV' => 'Dividende',
            'ERN' => 'Unternehmensergebnis',
            'FNG' => 'Finanzierungen, Kapitalerhöhungen',
            'GEN' => 'Restrukturierung und Turn-Around',
            'IPO' => 'Going Public, Bookbuilding, Greenshoe',
            'ITV' => 'Interview',
            'JNV' => 'Joint Ventures, Kooperationen',
            'LIC' => 'Lizenz',
            'MGT' => 'Personalien, Management, Verwaltungsrat',
            'MNA' => 'Übernahmen, Akquisitionen, Fusionen, MBOs',
            'NAV' => 'Innere Werte',
            'ORD' => 'Neue Aufträge',
            'PAN' => 'Beteiligungsinformationen (SHAB)',
            'PLO' => 'Betriebseröffnung',
            'PRD' => 'Neue Produkte und Dienstleistungen',
            'RAD' => 'Forschung und Entwicklung',
            'REG' => 'Aufsichtsbehörden, Regulierungen',
            'RTG' => 'Kredit-Ratings',
            'UNP' => 'Unternehmensporträts',
            'BND' => 'Anleihen, Bonds',
            'CLO' => 'Börse: Schlussbericht',
            'COM' => 'Rohstoffe, Warenmärkte',
            'FON' => 'Investmentfonds',
            'FRX' => 'Devisenmarkt',
            'FUT' => 'Derivate',
            'MKT' => 'Börse: Verlaufsbericht',
            'OPE' => 'Börse: Eröffnungsbericht',
            'RUM' => 'Marktgespräch, Gerüchte',
            'STK' => 'Markt und Börse',
            'ANL' => 'Finanzanalysen',
            'FIT' => 'Finanzanalysen-Tabelle',
            'OUT' => 'Ausblicke',
            'CBK' => 'Zentralbanken',
            'DBT' => 'Staatsverschuldung',
            'ECO' => 'Volkswirtschaft',
            'FIS' => 'Staatshaushalt und Steuern',
            'IRT' => 'Zinsen/Leitzinsen',
            'LAB' => 'Arbeit, Soziales',
            'MON' => 'Geld- und Finanzpolitik',
            'POL' => 'Politik, Regierung, Parlament, Bundesverwaltung',
            'TRA' => 'Handelspolitik, WTO',
            'CAL' => 'Kalender, Tagesvorschauen, Termine',
            'DIG' => 'Presseschauen',
            'FOC' => 'Schwerpunkt-, Hintergrundberichte',
            'RND' => 'Zusammenfassung: Meldung, welche die Fakten eines Ereignisses zusammenfasst',
            'SER' => 'Notizen und Hinweise an Kunden',
            'SOM' => 'Social Media',
            'SUM' => 'Überblick',
            'ART' => 'Kultur, Kunst, Unterhaltung',
            'BLV' => 'Boulvard',
            'DIS' => 'Katastrophe und Unglück',
            'HEA' => 'Medizin, Gesundheit',
            'LAW' => 'Justiz, Kriminalität',
            'NAT' => 'Umwelt',
            'NEW' => 'Allgemeine News',
            'RLG' => 'Religion, Weltanschauung',
            'SCN' => 'Wissenschaft, Technik',
            'SPO' => 'Sport',
            'OTS' => 'Adhoc-Meldungen, Originaltext-Service',
        ],
        'branche' => [
            'AIR' => 'Flug- und Raumfahrttechnik',
            'ALC' => 'Alkoholische Getränke/Brauerei',
            'AUT' => 'Automobilindustrie und Zulieferer',
            'BLD' => 'Baustoff',
            'BNK' => 'Banken',
            'BTC' => 'Biotechnologie',
            'CHM' => 'Chemie',
            'CMP' => 'Computer-Hardware und Informationstechnologie',
            'CNS' => 'Unternehmensberatung',
            'CON' => 'Bauindustrie',
            'CPL' => 'Öffentlich-rechtliche Körperschaften',
            'CSM' => 'Konsumgüter',
            'DRG' => 'Pharmaindustrie',
            'ECM' => 'E-Commerce',
            'EDU' => 'Erziehung, Ausbildung',
            'ELE' => 'Halbleiter und Elektronik',
            'EMP' => 'Arbeitsvermittlung',
            'ENE' => 'Energiewirtschaft und Versorger',
            'ENG' => 'Maschinenbau',
            'ENV' => 'Umwelt und Recycling',
            'FIN' => 'Finanzdienstleister',
            'FOO' => 'Nahrungsmittel',
            'GLD' => 'Edelmetall',
            'HTH' => 'Gesundheitsdienst',
            'IND' => 'Indizes (SMI, DAX, DJ, etc)',
            'INS' => 'Versicherungen',
            'INT' => 'Internet und Software',
            'IRO' => 'Eisen/Stahl',
            'LEI' => 'Freizeitindustrie, Tourismus',
            'LUX' => 'Uhren, Schmuck, Luxusgüter',
            'MDS' => 'Medizinaltechnik',
            'MIN' => 'Bergbau',
            'NKL' => 'Nicht klassifiziert/nicht klassifizierbar',
            'OIL' => 'Öl- und Gasindustrie',
            'ONF' => 'Nichteisenmetall',
            'PAK' => 'Papier und Verpackung',
            'PAP' => 'Forstwirtschaft',
            'PLN' => 'Landwirtschaft',
            'PRO' => 'Immobilien',
            'PUB' => 'Druck, Medien, Verlage',
            'RTS' => 'Detailhandel',
            'SFT' => 'Nicht alkoholische Getränke',
            'TEL' => 'Telekommunikations-Anbieter',
            'TEX' => 'Textil und Bekleidung',
            'TLE' => 'Telekommunikationsausrüster',
            'TOB' => 'Tabak',
            'TRD' => 'Grosshandel',
            'TRN' => 'Transport und Logistik',
            'TST' => 'Beteiligungsgesellschaften',
        ]
    ];

    public function get_monitor_id()
    {
        return MON_ID_AWP;
    }

    public function getTimeStamp()
    {
        return $this->cleanTimestamp(strip_tags($this->getField('dateTime')));
    }

    public function getText()
    {
        $this->applyMap($this->placeholderArray_['rubrik'],$this->replacementMap_['rubrik']);
        $this->applyMap($this->placeholderArray_['branche'],$this->replacementMap_['branche']);

        $a =  $this->headLine()
            . $this->bodyContent()

            . $this->fullName()
            . $this->branch()
            . $this->rubrik()
            . $this->isin()
            . $this->ticker()
            . $this->telekurs()
            . $this->handelsRegisterNr()
        ;

        /* todo: reactivate this to ignore <pre> tags in code */
//        $replace = [
//            ['<pre>','</pre>'],
//            ['[PrEtAgStArT]','[PrEtAgEnD]']
//        ];
//        $a = str_replace($replace[0],$replace[1],$a);
        $a = html_entity_decode($a);
        $a = strip_tags($a);
//        $a = str_replace($replace[1],$replace[0],$a);
        return $a;
    }

    private function headLine()
    {
        $txt = $this->implodeField('headLine', "\n");
        if ($txt == '') return '';
        return $txt . "\n";
    }

    private function bodyContent()
    {
        return $this->getField('bodyContent') . "\n";
    }

    private function fullName()
    {
        $txt = $this->implodeField('fullName', ', ');
        if ($txt == '') return '';
        return 'Name: ' . $txt . "\n";
    }

    private function telekurs()
    {
        $txt = $this->implodeField('telekurs', ', ');
        if ($txt == '') return '';
        return 'Telekurs: ' . $txt . "\n";
    }

    private function ticker()
    {
        $txt = $this->implodeField('ticker', ', ');
        if ($txt == '') return '';
        return 'Ticker: ' . $txt . "\n";
    }

    private function handelsRegisterNr()
    {
        if (!isset($this->placeholderArray_['handelsRegisterNr'])) {
            return '';
        }

        /* remove "000000" */
        $a = array_filter($this->placeholderArray_['handelsRegisterNr'],function($a) {
            return ($a !== "000000");
        });

        if (empty($a)) {
            return '';
        }

        $txt = implode(', ', $a);
        return 'Handelsregister Nr: ' . $txt . "\n";
    }

    private function isin()
    {
        $txt = $this->implodeField('isin', ', ');
        if ($txt == '') return '';
        return 'ISIN: ' . $txt . "\n";
    }

    private function branch()
    {
        $txt = $this->implodeField('branche', ', ');
        if ($txt == '') return '';
        return 'Branche: ' . $txt . "\n";
    }

    private function rubrik()
    {
        $txt = $this->implodeField('rubrik', ', ');
        if ($txt == '') return '';
        return 'Rubrik: ' . $txt . "\n";
    }
}
