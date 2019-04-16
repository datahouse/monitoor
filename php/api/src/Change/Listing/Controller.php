<?php

namespace Datahouse\MON\Change\Listing;

use Datahouse\MON\BaseController;
use \Datahouse\MON\Request as Request;
use Datahouse\MON\Types\ChangeFilter;
use Datahouse\MON\UserToken;

/**
 * Class Controller
 *
 * @package Change
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Controller extends BaseController
{

    /**
     * @param Model     $model     the model
     * @param ViewModel $viewModel the view model
     * @param Request   $request   the request
     * @param UserToken $userToken the user token
     */
    public function __construct(
        Model $model,
        ViewModel $viewModel,
        Request $request,
        UserToken $userToken
    ) {
        parent::__construct($model, $viewModel, $request, $userToken);
    }

    /**
     * control
     *
     *
     * @return void
     */
    public function control()
    {
        $this->viewModel->setLang($this->request->getLang());
        $this->viewModel->setUserId($this->userToken->getUserId());
        $filter = new ChangeFilter();
        if (array_key_exists('alert_id', $_GET)) {
            $filter->setAlertId($_GET['alert_id']);
        }
        if (array_key_exists('url_id', $_GET)) {
            $filter->setUrlId($_GET['url_id']);
        }
        if (array_key_exists('url_group_id', $_GET)) {
            $filter->setUrlGroupId($_GET['url_group_id']);
        }
        if (array_key_exists('start_date', $_GET)) {
            $filter->setStartDate($_GET['start_date']);
        }
        if (array_key_exists('keyword', $_GET)) {
            $filter->setKeyword($_GET['keyword']);
        }
        if (array_key_exists('favorites', $_GET) && $_GET['favorites'] == true) {
            $this->viewModel->setShowFavorites(true);
        }
        $this->viewModel->setChangeFilter($filter);
        $offset = null;
        $size = null;
        $sort = null;
        if (array_key_exists('size', $_GET)) {
            $size = $_GET['size'];
        }
        if (array_key_exists('sort', $_GET)) {
            $sort = $_GET['sort'];
        }
        if (array_key_exists('offset', $_GET)) {
            $offset = $_GET['offset'];
        }
        $this->viewModel->setPagingAndSorting($offset, $size, $sort);
        if (array_key_exists('demo', $_GET)) {
            $this->viewModel->setDemoRequest($_GET['demo']);
        }
    }
}
