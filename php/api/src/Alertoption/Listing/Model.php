<?php

namespace Datahouse\MON\Alertoption\Listing;

use Datahouse\MON\I18\I18;
use Datahouse\MON\Types\Gen\AlertOption;

/**
 * Class Model
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends \Datahouse\Framework\Model
{

    /**
     * @param \PDO $pdo the pdo
     * @param I18  $i18 the i18
     */
    public function __construct(\PDO $pdo, I18 $i18)
    {
        $this->pdo = $pdo;
        $this->i18 = $i18;
    }

    /**
     * readAlertOptionList
     *
     * @param int $langCode the lang code
     *
     * @return array
     * @throws \Exception
     */
    public function readAlertOptionList($langCode)
    {
        try {
            $query = 'SELECT alert_option_id, alert_option_name ';
            $query .= 'FROM alert_option';
            $optionList = array();
            foreach ($this->pdo->query($query) as $res) {
                $option = new AlertOption();
                $option->setId($res['alert_option_id']);
                $option->setTitle(
                    $this->i18->translate(
                        'alert_option_' . $res['alert_option_id'],
                        $langCode
                    )
                );
                $optionList[] = $option;
            }
            return $optionList;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ');
        }
    }
}
