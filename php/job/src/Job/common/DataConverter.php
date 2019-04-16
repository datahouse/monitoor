<?php

abstract class DataConverter
{

    /* @var array */
    protected $placeholderDefinition_;
    /* @var string */
    protected $linkDefinition_;
    /* @var string */
    protected $topDefinition_;
    /* @var string[][] */
    protected $placeholderArray_ = [];
    /*

      Placeholder Array Example
        first level:
            empty string for getting the contents of the tag as a text (i.e.//p[@class="name"] => <p class="name">this is a text</p>)
            attribute name to get the value of an attribute (i.e. <input Value="this is a text" />)

      $placeholderArray_ = [
       '' => [
            'content'    => '//body.content',
            'cantonName' => '//PUB.HEAD/CANTON.NAME',
            'logDate'    => '//PUB.DATE',
            'firmUID'    => '//FIRM[@INFO.VER=\'NEW\']/FIRM.UID'
        ],
        'Value' => [
            'branch_value' => '//Property[@FormalName="Branche"]'
        ]
     ]
     */

    /* @var string */
    protected $includeRequirement_ = '';
    /* @var string */
    protected $idTag_ = '/meta/id';
    /* @var bool */
    protected $usesId_ = true;
    /* @var string[] */
    protected $neededAddresses_ = [];

    function __construct() {
        $this->addAddresses();
    }

    /**
     * adds a standard definition of an address for instance for creditor, debitor
     * just so code is less clunky and all addresses seem to have the same possible fields
     */
    protected function addAddresses()
    {
        foreach ($this->neededAddresses_ as $placeholderName => $contentDef) {
            $contentName = '/content/' . $contentDef;
            $addressPlaceholders = [
                /* address is either a person or a name */
                $placeholderName . 'PersName'                   => $contentName . '/person/name',
                $placeholderName . 'PersFirstname'              => $contentName . '/person/prename',
                //'schuldnerPersWork' => '//DEBIT.PERS/WORK', /* ask dwe */
                $placeholderName . 'PersBirthCountry'           => $contentName . '/person/countryOfOrigin/name/de',
                $placeholderName . 'PersBirthPlace'             => $contentName . '/person/placeOfOrigin',
                $placeholderName . 'PersBirthDate'              => $contentName . '/person/dateOfBirth',
                $placeholderName . 'PersStreet'                 => $contentName . '/person/addressSwitzerland/street',
                $placeholderName . 'PersHouseNumber'            => $contentName . '/person/addressSwitzerland/houseNumber',
                $placeholderName . 'PersZIP'                    => $contentName . '/person/addressSwitzerland/swissZipCode',
                $placeholderName . 'PersCity'                   => $contentName . '/person/addressSwitzerland/town',
                $placeholderName . 'PersForeignAddress'         => $contentName . '/person/addressForeign/addressCustomText',
                $placeholderName . 'PersForeignCountry'         => $contentName . '/person/addressForeign/country/name/de',
                $placeholderName . 'PersForeignIso'             => $contentName . '/person/addressForeign/country/isocode',
                $placeholderName . 'PersInfo'                   => $contentName . '/person/personInformation',

                /* firm-address is either a Firm with UID and address, or a "customAddress" */
                $placeholderName . 'FirmName'                   => $contentName . '/companies/company/name',
                $placeholderName . 'FirmCountry'                => $contentName . '/companies/company/country/name/de',

                $placeholderName . 'FirmCustomAddress'          => $contentName . '/companies/company/customAddress',

                $placeholderName . 'FirmUid'                    => $contentName . '/companies/company/uid',
                $placeholderName . 'FirmStreet'                 => $contentName . '/companies/company/address/street',
                $placeholderName . 'FirmHouseNumber'            => $contentName . '/companies/company/address/houseNumber',
                $placeholderName . 'FirmZIP'                    => $contentName . '/companies/company/address/swissZipCode',
                $placeholderName . 'FirmCity'                   => $contentName . '/companies/company/address/town',
            ];
            $this->placeholderDefinition_[''] = array_merge($this->placeholderDefinition_[''], $addressPlaceholders);
        }
    }

    public abstract function get_monitor_id();

    public function getPlaceholderDefinition()
    {
        return $this->placeholderDefinition_;
    }
    
    public function getTopDefinition()
    {
        return $this->topDefinition_;
    }

    public function getFilterDefintion()
    {
        return $this->linkDefinition_;
    }

    public function setPlaceholderArray($placeHolderArray)
    {
        $this->placeholderArray_ = $placeHolderArray;
    }

    /**
     * @return string
     */
    public function getIncludeRequirement()
    {
        return $this->includeRequirement_;
    }

    /**
     * @return bool
     */
    public function usesId()
    {
        return $this->usesId_;
    }

    /**
     * @return string
     */
    public function getIdTag()
    {
        return $this->idTag_;
    }

    abstract public function getText();
    
    protected function cleanTimestamp($text)
    {
        $t = new DateTime($text);
        $text = $t->format('Y-m-d\TH:i:s');
        return $text;
    }

    protected function cleanText($text)
    {
        $text = strip_tags($text);
        do {
            $oldRow = $text;
            $text = str_replace('  ', ' ', $text);
            $text = str_replace(',,', ',', $text);
            $text = str_replace(' ,', ',', $text);
            $text = str_replace('..', '.', $text);
            $text = str_replace(' .', '.', $text);
            $text = str_replace("\n\n", "\n", $text);
            $text = str_replace("\n.\n", "\n", $text);
        } while ($oldRow != $text);
        $text = str_replace("\r", '', $text); /* used to force 2 new lines */
        return $text;
    }

    /**
     * returns true only if all given fields are null or = ""
     * @return bool
     */
    protected function checkAllNull()
    {
        foreach (func_get_args() as $name) {
            if (isset($this->placeholderArray_[$name][0]) && !is_null($this->placeholderArray_[$name][0]) && $this->placeholderArray_[$name][0] != '') {
                return false;
            }
        }
        return true;
    }

    protected function getField($name, $nr = 0)
    {
        if (isset($this->placeholderArray_[$name][$nr])) {
            return $this->placeholderArray_[$name][$nr];
        } else {
            return null;
        }
    }

    protected function implementField($name, $pre, $post, $nr = 0)
    {
        return $this->checkAllNull($name) ? '' : ($pre . $this->placeholderArray_[$name][$nr] . $post);
    }

    protected function implodeField($name, $separator)
    {
        if (!isset($this->placeholderArray_[$name])) {
            return '';
        }
        return implode($separator, $this->placeholderArray_[$name]);
    }
    
    protected function getFieldCount($name)
    {
        return count($this->placeholderArray_[$name]);
    }

    protected function applyMap(&$valueArray,$replaceArray)
    {
        if (is_null($valueArray)) {
            return ;
        }
        foreach ($valueArray as &$item) {
            if (isset($replaceArray[$item])) {
                $item = $replaceArray[$item];
            }
        }
    }

    public function getTimeStamp()
    {
        return $this->cleanTimestamp(strip_tags($this->getField('pubDate')));
    }

    protected function address($type, $preIfNotEmpty, $postIfNotEmpty)
    {
        if ($this->checkAllNull($type . 'FirmCustomAddress')) {
            $firm = $this->addressFirmWithUid($type);
        } else {
            $firm = $this->addressFirmNoUid($type);
        }

        $address = $this->addressPerson($type) . $firm;
        return $address == ''
            ? ''
            : ($preIfNotEmpty . $address . $postIfNotEmpty);
    }

    protected function submittor()
    {
        return $this->addressFirmWithUid('registration');
    }

    private function addressPerson($type)
    {
        $type = $type .'Pers';
        if ($this->checkAllNull(
            $type . 'Name',           $type . 'Firstname',      $type . 'BirthCountry',
            $type . 'BirthPlace',     $type . 'BirthDate',      $type . 'Street',
            $type . 'HouseNumber',    $type . 'ZIP',            $type . 'City',
            $type . 'ForeignAddress', $type . 'ForeignCountry', $type . 'ForeignIso',
            $type . 'Info')
        ) {
            return '';
        }

        if (!$this->checkAllNull($type . 'ForeignAddress',$type . 'ForeignCountry',$type . 'ForeignIso')) {
            $address = $this->getField($type . 'ForeignAddress') . ', ' . $this->getField($type . 'ForeignCountry');
        } else {
            $address = $this->getField($type . 'Street') . ' ' . $this->getField($type . 'HouseNumber') . ', '
            . $this->getField($type . 'ZIP') . ' '
            . $this->getField($type . 'City');
        }
        if ($this->checkAllNull($type . 'BirthDate', $type . 'BirthCountry', $type . 'BirthPlace')) {
            $birth = '';
        } else {
            $birth = 'geb. '
                . $this->implementField($type . 'BirthDate', '' , ', ')
                . ($this->checkAllNull($type. 'BirthPlace', $type . 'BirthCountry')
                    ? ''
                    : (' in ' . $this->implementField($type . 'BirthPlace', '', ',')
                        . $this->implementField($type . 'BirthCountry', '', ',')
                    )
                ) . ', ';
        }

        return $this->implementField($type . 'Name', '', ', ')
            . $this->implementField($type . 'Firstname', '', ', ')
            . $birth
            . (str_replace([' ',','], '', $address) == '' ? '' : 'Wohnhaft: ' . $address)
            . $this->implementField($type . 'Info', "\n", '');
    }

    private function addressFirmWithUid($type)
    {
        $type .= 'Firm';
        if ($this->checkAllNull(
            $type . 'Name',   $type . 'Country',     $type . 'Uid',
            $type . 'Street', $type . 'HouseNumber', $type . 'ZIP',
            $type . 'City'
        )) {
            return '';
        }
        $r = $this->implementField($type . 'Name', '', ', ')
            . $this->implementField($type . 'Street', '' ,', ')
            . $this->implementField($type . 'ZIP', '', ' ')
            . $this->getField($type . 'City')
            . $this->implementField($type . 'Country', ', ', '')
            . $this->implementField($type . 'Uid', ', UID: ', '');
        return $r;
    }

    private function addressFirmNoUid($type)
    {
        $type .= 'Firm';
        if ($this->checkAllNull(
            $type . 'Name',
            $type . 'Country',
            $type . 'CustomAddress'
        )) {
            return '';
        }
        return
            str_replace("\n", ', ', $this->implementField($type . 'Name', ', ', '')
            . $this->implementField($type . 'CustomAddress', ', ', '')
            . $this->implementField($type . 'Country', ', ', ''));
    }

    protected function notice($preIfNotEmpty, $postIfNotEmpty)
    {
        $ret = '';
        if ($this->checkAllNull('locationCirculationAuthority','legalRemedy','additionalLegalRemedy')) {
            return $ret;
        }
        $ret .= $this->implementField('locationCirculationAuthority', '', "\n")
            . $this->implementField('legalRemedy', '', "\n")
            . $this->implementField('additionalLegalRemedy', '', "\n");
        return $preIfNotEmpty . trim($ret, " \n") . $postIfNotEmpty;
    }
    
}
