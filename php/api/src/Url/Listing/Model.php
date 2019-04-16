<?php

namespace Datahouse\MON\Url\Listing;

use Datahouse\MON\Types\Gen\Url;
use Datahouse\MON\Types\Gen\UrlList;

/**
 * Class Model
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends \Datahouse\Framework\Model
{

    private $pdo;

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * readAlertList
     *
     * @param int    $offset  the offset
     * @param int    $size    the size
     * @param string $sorting the sorting string
     * @param Url    $url     the url filter
     * @param int    $userId  the user id
     *
     * @return UrlList
     * @throws \Exception
     */
    public function readUrlList(
        $offset,
        $size,
        $sorting,
        Url $url,
        $userId
    ) {
        $urlListResponse = new UrlList();

        $fromString = ' FROM url WHERE (EXISTS ';
        $fromString .= ' (SELECT url_id FROM ACCESS_CONTROL a WHERE user_id = :userId';
        $fromString .= ' AND url.url_id = a.url_id) ';
        $fromString .= ' OR EXISTS (SELECT url_id FROM ACCESS_CONTROL a ';
        $fromString .= ' WHERE url.url_id = a.url_id AND ';
        $fromString .= ' user_group_id = (SELECT user_group_id FROM mon_user ';
        $fromString .= ' WHERE user_id=:userId AND user_group_id IS NOT NULL))) ';
        $fromString .= ' AND url_active ';
        $bindParams = $this->createFilter($url, $whereFilter);
        $bindParams[':userId'] =
            [$userId, \PDO::PARAM_INT];
        $query = '';
        try {
            $urls = array();
            $query .= 'SELECT count(*) as count ' . $fromString . $whereFilter;
            $count = 0;
            $stmt = $this->pdo->prepare($query);
            foreach ($bindParams as $bindParam => $value) {
                $stmt->bindValue($bindParam, $value[0], $value[1]);
            }
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $count = $res['count'];
            }
            if ($count > 0) {
                $query = 'SELECT url_id, url_title, url ' . $fromString . $whereFilter;
                $query .= $this->getOrderBy($sorting) .
                    $this->createLimit($offset, $size);
                $stmt = $this->pdo->prepare($query);
                foreach ($bindParams as $bindParam => $value) {
                    $stmt->bindValue($bindParam, $value[0], $value[1]);
                }
                $stmt->execute();
                foreach ($stmt->fetchAll() as $res) {
                    $urls[] = $this->getUrl($res);
                }
            }
            $urlListResponse->count = $count;
            $urlListResponse->setUrlItems($urls);
            return $urlListResponse;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * createLimit
     *
     * @param int $offset the offset
     * @param int $size   the size
     *
     * @return string
     */
    private function createLimit($offset, $size)
    {
        $off = ' OFFSET ' . intval($offset);
        $limit = '';
        if ($size != null) {
            $limit = ' LIMIT ' . $size;
        }
        return $off . $limit;
    }

    /**
     * getOrderBy
     *
     * @param string $sorting the sorting string
     *
     * @return string
     */
    private function getOrderBy($sorting)
    {
        $orderBy = '';
        //sorting
        $sort = explode(',', $sorting);
        if ($sorting != null && strlen($sorting) > 0) {
            $orderBy .= ' ORDER BY ';
        }
        foreach ($sort as $sortOrder) {
            $order = strtolower(str_replace('-', '', $sortOrder));
            switch ($order) {
                case 'title':
                    $orderBy .= ' url_title ';
                    $orderBy .= $this->getSortOrder($sortOrder);
                    break;
                case 'url':
                    $orderBy .= ' url ';
                    $orderBy .= $this->getSortOrder($sortOrder);
                    break;
            }
        }
        return rtrim($orderBy, ',');
    }

    /**
     * getSortOrder
     *
     * @param string $value the value
     *
     * @return string
     */
    private function getSortOrder($value)
    {
        if (strpos($value, '-') !== false) {
            return ' DESC,';
        }
        return ' ASC,';
    }

    /**
     * createFilter
     *
     * @param Url    $url         the url
     * @param string $wherefilter the where string
     *
     * @return array
     */
    private function createFilter(Url $url, &$wherefilter)
    {
        $bindParams = array();
        if ($url != null) {
            if ($this->checkString($url->getTitle()) != null) {
                $wherefilter .= ' AND url_title ILIKE :titel ';
                $bindParams[':titel'] =
                    ['%' . $url->getTitle() . '%', \PDO::PARAM_STR];
            }
            if ($this->checkString($url->getUrl()) != null) {
                $wherefilter .= ' AND url ILIKE :url ';
                $bindParams[':url'] =
                    ['%' . $url->getUrl() . '%', \PDO::PARAM_STR];
            }
        }
        return $bindParams;
    }

    /**
     * checkString
     *
     * @param string $value the value
     *
     * @return string
     */
    private function checkString($value)
    {
        if ($value != null && strlen($value) > 0) {
            return $value;
        }
        return null;
    }

    /**
     * getUrl
     *
     * @param array $res the results
     *
     * @return Url
     */
    private function getUrl(array $res)
    {
        $url = new Url();
        $url->setExternal((0 === strpos($res['url'], 'external')));
        $url->setId($res['url_id']);
        $url->setTitle($res['url_title']);
        $url->setUrl($res['url']);
        return $url;
    }
}
