<?php

namespace Datahouse\MON\I18\Trans;

use Datahouse\MON\Types\Gen\Error;

/**
 * Class ViewModel
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends \Datahouse\MON\ViewModel
{
    private $lang = 1;
    private $part = null;
    private $rptType = 'I18/trans';

    /**
     * setLang
     *
     * @param int $lang the lang
     *
     * @return void
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * setId
     *
     * @param int $id the id
     *
     * @return void
     */
    public function setPart($part)
    {
        $this->part = $part;
    }


    /**
     * @param Model $model the model
     */
    public function __construct(Model $model)
    {
        parent::__construct($model);
    }

    /**
     * getData
     *
     *
     * @return bool | Error
     */
    public function getData()
    {
        try {
            return $this->model->getTranslation($this->part, $this->lang);
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                array('Unexpected Error'),
                $this->rptType
            );
        }
    }
}
