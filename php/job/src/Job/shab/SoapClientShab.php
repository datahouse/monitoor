<?php
class SoapClientShab
{
    private $username_ = 'datahouse';
    private $password_ = '<redacted>';
    private $subscriptionId_ = '112362';


    private function getHeaders() {
        $h = array(
            'username: ' . $this->username_,
            'password: ' . $this->password_,
            'POST /soapserver HTTP/1.1',
            'SOAPAction: ""',
            'Accept: text/xml, multipart/related, text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2',
            'Content-Type: text/xml; charset=utf-8',
            'User-Agent: Java/1.6.0_23',
            'Host: www.shab.ch',
            'Connection: keep-alive',
            'Content-Length: 273'
        );
        return implode("\r\n",$h) . "\r\n";
    }

    public function __construct($username, $password, $subscriptionId)
    {
        $this->username_ = $username;
        $this->password_ = $password;
        $this->subscriptionId_ = $subscriptionId;

        $url = 'http://www.shab.ch/soapserver?wsdl';
        $params = array(
            'location' => $url,
            'uri' => $url,
            'trace' => 1,
            "stream_context" => stream_context_create(array("http"=>array(
                "header"=> $this->getHeaders()
            )))
        );
        $this->instance = new soapClient($url,$params);

        if (!$this->getAuthenticate()) {
            throw new exception('SOAP Authentication problem');
        }
    }

    public function getAuthenticate()
    {
        return $this->instance->__soapCall('getAuthentication', array());
    }

    /**
     * returns a datestring YYYY-MM-DD
     * if no $date given, returns Today
     *
     * @param $date
     * @return string
     */
    private function conformDate($date = '')
    {
        $d = new DateTime($date);
        return $d->format('Y-m-d');
    }

    public function getNoticeListForSubscriber($publishDate = '')
    {
        $publishDate = $this->conformDate($publishDate);

        return $this->instance->__soapCall('getNoticeListForSubscriber', array(
            'publishDate' => $publishDate,
            'subscriptionId' => $this->subscriptionId_
        ));
    }

    /**
     *
     * Does not work
     *
     * @param $publishDateFrom
     * @param $publishDateTo
     * @return mixed
     */
    public function getNoticeListForSubscriberDateRange($publishDateFrom, $publishDateTo)
    {
        $publishDateFrom = $this->conformDate($publishDateFrom);
        $publishDateTo = $this->conformDate($publishDateTo);

        return $this->instance->__soapCall('getNoticeListForSubscriber', array(
                'publishDateFrom' => $publishDateFrom,
                'publishDateTo' => $publishDateTo,
                'subscriptionId' => $this->subscriptionId_
            ));
    }

    public function getNoticeXml($documentId)
    {
        return $this->instance->__soapCall('getNoticeXml',array('docId' => $documentId));
    }

    public function getNoticeXmls($publishDate = '')
    {
        $publishDate = $this->conformDate($publishDate);
        return $this->instance->__soapCall('getNoticeXmls',array('publishDate' => $publishDate));
    }

    public function getNoticeHtml($documentId)
    {
        return $this->instance->__soapCall('getNoticeHtml',array('docId' => $documentId));
    }

    public function getNoticePdf($documentId)
    {
        return $this->instance->__soapCall('getNoticePdf',array('docId' => $documentId));
    }

}

