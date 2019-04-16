<?php
/**
 * Class SendJson
 * For Sending Data to the Monitoor API
 * must be initialised with the login details and user_id
 */
class SendJson
{
    private $token_ = null;
    private $loginUrl_ = null;
    private $sendUrl_ = null;
    private $userName_ = null;
    private $password_ = null;
    private $id_ = null;
    private $debug_ = 0;
    private $doSend_ = true;

    /**
     * SendJson constructor.
     * @param null $userName
     * @param null $password
     * @param null $loginUrl
     * @param null $sendUrl
     * @param null $id
     */
    public function __construct($userName, $password, $loginUrl, $sendUrl)
    {
        $this->userName_ = $userName;
        $this->password_ = $password;
        $this->loginUrl_ = $loginUrl;
        $this->sendUrl_ = $sendUrl;
    }

    /**
     * @param $val bool
     */
    public function setDebug($val)
    {
        $this->debug_ = $val;
    }

    public function setDoNotSend($val = true)
    {
        $this->doSend_ = !$val;
    }

    public function setMonitoorId($id)
    {
        $this->id_ = intval($id);
    }

    /**
     * The ID for the user is saved in this class, so it get's added here
     * @param $data
     * @return mixed
     */
    public function wrap_and_send($data)
    {
        $data = array(
            'id' => $this->id_,
            'data' => array($data)
        );
        return $this->send($data);
    }

    /**
     * @param array $data
     * @return mixed
     * @throws exception
     */
    public function send(array $data)
    {
        if (!$this->doSend_) {
            if ($this->debug_) logger::log(var_export($data,true));
            return false;
        }

        $this->loginIfNecessary();
        $this->communicate($this->sendUrl_, $data);
        return true;
    }

    /**
     * @param $url
     * @param array $data
     * @return mixed
     * @throws exception
     */
    private function communicate($url, array $data)
    {
        if ($this->debug_) echo '**********************************'."\n";
        $jsonData = json_encode($data);
        if ($this->debug_) logger::log('Data: '. var_export($jsonData,true));
        if ($this->debug_) logger::log('  to: ' . $url);

        $headers = array(
            'Content-Type:application/json',
            'Accept:application/json',
            'Content-Length:' . strlen($jsonData)
        );
        if (!is_null($this->token_)) {
            $headers[] = 'auth-token:' . $this->token_;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF8');
        curl_setopt($ch, CURLOPT_HEADER, 'UTF8');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if ($this->debug_) echo 'Result: '.strip_tags($result) . "\n";
        if(curl_errno($ch)){
            throw new exception('Request Error:' . curl_error($ch)); /* todo how should exceptions be used? */
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code != 200) {
            throw new exception('connection problem'); /* todo how should exceptions be used? */
        }

        return json_decode($result);
    }

    /**
     * @throws exception
     * if token isn't set, login and get it from Monitoor
     */
    private function loginIfNecessary()
    {
        if (is_null($this->token_)) {
            $data = array('username' => $this->userName_, 'password' => $this->password_);
            $t_array = $this->communicate($this->loginUrl_, $data);
            $this->token_ = $t_array->token->id;
        }
    }
}
