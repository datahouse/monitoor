<?php

namespace Datahouse\MON\Types;

/**
 * Class Report
 *
 * @package
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2016 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Report
{

    private $userId;
    private $firstName;
    private $lastName;
    private $company;
    private $activation;
    private $cancellation;
    private $email;
    private $price;

    /**
     * getUserId
     *
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * setUserId
     *
     * @param mixed $userId userId
     * @return void
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * getFirstName
     *
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * setFirstName
     *
     * @param mixed $firstName firstName
     * @return void
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * getLastName
     *
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * setLastName
     *
     * @param mixed $lastName lastName
     * @return void
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * getCompany
     *
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * setCompany
     *
     * @param mixed $company company
     * @return void
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * getActivation
     *
     * @return mixed
     */
    public function getActivation()
    {
        return $this->activation;
    }

    /**
     * setActivation
     *
     * @param mixed $activation activation
     * @return void
     */
    public function setActivation($activation)
    {
        $this->activation = $activation;
    }

    /**
     * getCancellation
     *
     * @return mixed
     */
    public function getCancellation()
    {
        return $this->cancellation;
    }

    /**
     * setCancellation
     *
     * @param mixed $cancellation cancellation
     * @return void
     */
    public function setCancellation($cancellation)
    {
        $this->cancellation = $cancellation;
    }

    /**
     * getEmail
     *
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * setEmail
     *
     * @param mixed $email email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * getPrice
     *
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * setPrice
     *
     * @param mixed $price price
     * @return void
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }
}
