<?php

namespace Datahouse\MON;

use Datahouse\MON\Exception\BadRequestException;

/**
 * Class Request
 *
 * @package Request
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2018 by Datahouse AG
 */
class Request
{

    /**
     * @var array
     */
    private $request = array();
    private $langId = 1;
    private $id;
    private $name;
    private $jsonReqParams;
    private $requestMethod;

    /**
     * @var array
     */
    protected $langMap = array(
        'de' => 1,
        'en' => 2
    );

    /**
     * @param array $request the req
     */
    public function __construct(array $request)
    {
        $this->request = array_slice($request, 3);

        if (array_key_exists(0, $this->request) &&
            is_numeric($this->request[0])
        ) {
            $this->id = array_shift($this->request);
        }
        if (
            (
                array_key_exists(1, $request) &&
                $request[1] === 'i18'
            ) && (
                array_key_exists(2, $request) &&
                $request[2] === 'trans'
            )
        ) {
            $this->name = array_shift($this->request);
        }

        if (array_key_exists(0, $this->request) &&
            array_key_exists($this->request[0], $this->langMap)
        ) {
            $this->langId = $this->langMap[array_shift($this->request)];
        }
        $this->jsonReqParams = json_decode(file_get_contents('php://input'));
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
    }

    /**
     * getLang
     *
     *
     * @return int
     */
    public function getLang()
    {
        return $this->langId;
    }

    /**
     * getJsonReqParams
     *
     * @return mixed
     */
    public function getJsonReqParams()
    {
        return $this->jsonReqParams;
    }

    /**
     * returns the request medhod.
     *
     * @return mixed [String]
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * getId
     *
     *
     * @return int
     * @throws BadRequestException
     */
    public function getId()
    {
        if (isset($this->id)) {
            return $this->id;
        }
        throw new BadRequestException('Missing id in request');
    }

    /**
     * getName
     *
     *
     * @return string
     * @throws BadRequestException
     */
    public function getName()
    {
        if (isset($this->name)) {
            return $this->name;
        }
        throw new BadRequestException('Missing name in request');
    }
}
