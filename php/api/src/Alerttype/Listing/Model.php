<?php

namespace Datahouse\MON\Alerttype\Listing;

use Datahouse\MON\I18\I18;
use Datahouse\MON\Model\AlertModel;

/**
 * Class Model
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends AlertModel
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
     * readAlertTypeList
     *
     * @param int $langCode the lang code
     * @param int $userId   the user
     *
     * @return array
     * @throws \Exception
     */
    public function readAlertTypeList($langCode, $userId)
    {
        try {
            return $this->getAlertTypes($langCode, $userId);
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ');
        }
    }
}
