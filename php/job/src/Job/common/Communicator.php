<?php

/**
 * Class Communicator
 * For GET / POST 
 */
class Communicator
{
    /**
     * @param string $url
     * @param string[] $post
     * @param string|null $customRequest
     * @param $fileHandler
     * @return array[string header, string body, int httpCode]
     * @throws Exception
     */
    public static function communication(
	    $url,
        array $post = [],
        $customRequest = null,
        $fileHandler = null
    ){

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF8');

        if (!empty($post)) {
            if ($customRequest !== 'PUT') {
                curl_setopt($ch, CURLOPT_POST, 1);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        if (!is_null($customRequest)) {
            if ($customRequest == 'PUT') {
                if (!is_null($fileHandler)) {
                    // CURLOPT_PUT=true expects the INFILE option
                    curl_setopt($ch, CURLOPT_PUT, 1);
                    curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_INFILE, $fileHandler);
                } else {
                    // CURLOPT_CUSTOMREQUEST=put uses the data from CURLOPT_POSTFIELDS
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                }
            } else {
                /* MKCOL, PROPFIND, DELETE */
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
            }
        }
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $output = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code == 401) {
            throw new exception('unauthorized: ' . $output);
        }
        if (!(($code >= 200 && $code <= 299) || $code == 404 || $code == 405 || $code == 409)) {
            throw new exception('connection problem:' . $code . ': ' .$output);
        }
        if ($output === false) {
            throw new exception('unknown error');
        }
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($output, 0, $header_size);
        $body = substr($output, $header_size);

        return ['header' => $header, 'body' => $body, 'httpCode' => $code];
    }

    /**
     * @param string $url
     * @param string[] $post
     * @param string|null $customRequest
     * @param $fileHandler
     * @return bool|\SimpleXMLElement
     * @throws Exception
     */
    public static function communicationGetXml($url, array $post = [], $customRequest = null, $fileHandler = null)
    {
        /* see https://www.sitepoint.com/simplexml-and-namespaces/ */
        $res = static::communication($url, $post, $customRequest, $fileHandler);
        $body = $res['body'];
        if ($body == '') {
            return true; /* other non-xml throws exception */
        }
        $a = @simplexml_load_string($body);
        if ($a === false) {
            throw new Exception($body);
        }
        return $a;
    }

}
