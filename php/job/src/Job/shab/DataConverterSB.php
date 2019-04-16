<?php
require_once(__DIR__ . '/../common/DataConverter.php');
require_once(__DIR__ . '/../common/Logger.php');

abstract class DataConverterSB extends DataConverter
{
    protected $placeholderDefinition_ = [];

    public function get_monitor_id()
    {
        return MON_ID_SCHULDBETREIBUNGEN;
    }


    protected function payDemand($postIfNotEmpty)
    {
        if ($this->checkAllNull('payDemandNb','payDemandDate')) {
            return '';
        }
        $ret = $this->implementField('payDemandNb', '', ' ') . $this->implementField('payDemandDate', 'vom ', '');
        return $ret == '' ? '' : ('Zahlungsbefehl: ' . $ret . $postIfNotEmpty);
    }

    protected function claim($postIfNotEmpty)
    {
        if (is_null($this->placeholderArray_['claimChf'])) {
            Logger::log("Warning: " . $this->placeholderDefinition_['']['claimChf'] . " not found");
            return '';
        }
        if (count($this->placeholderArray_['claimChf']) == 0) {
            return '';
        }
        $ret = "\r\nForderungen:\n";
        for ($i = 0; $i < count($this->placeholderArray_['claimChf']); ++$i){
            if ($this->getField('claimChf', $i) != '') {
                $ret .= $this->getField('claimChf', $i) . ' CHF';
                if ($this->getField('claimInterest', $i) != '') {
                    $ret .= ' nebst Zins zu ' . $this->getField('claimInterest', $i) . '%'
                        . ' seit ' . $this->getField('claimDate', $i) ;
                }
                $ret .= $this->implementField('claimDescription', "\n \r \r ", "\n");
                $ret .= '.' . "\n";
            }
        }
        return $ret . $postIfNotEmpty;
    }
    
}
