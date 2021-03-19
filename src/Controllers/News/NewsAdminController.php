<?php
namespace src\Controllers\News;

use src\Controllers\BaseController;
use src\Models\ChunkModels\PaginationModel;
use src\Models\News\News;
use src\Utils\DateTime\Converter;
use src\Utils\Uri\SimpleRouter;
use src\Utils\Uri\Uri;

class NewsAdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function isCallableFromRouter($actionName)
    {
        // all public methods can be called by router
        return true;
    }

    public function index()
    {
        $this->checkUserIsAdmin();

        $this->showAdminNews();
    }

    private function showAdminNews()
    {
        $paginationModel = new PaginationModel(10);
        $paginationModel->setRecordsCount(News::getAdminNewsCount());
        [$limit, $offset] = $paginationModel->getQueryLimitAndOffset();
        $this->view->setVar('paginationModel', $paginationModel);
        $this->showAdminNewsList(News::getAdminNews($offset, $limit));
    }

    public function saveNews()
    {
        $this->checkUserIsAdmin();

        $formResult = $_POST;
        if (! is_array($formResult)) {
            return false;
        }
        header('X-XSS-Protection: 0');
        if ($formResult['id'] != 0) {
            $news = News::fromNewsIdFactory($formResult['id']);
        } else {
            $news = new News();
            $news->setAuthor($this->loggedUser);
        }
        $news->loadFromForm($formResult);
        $news->setEditor($this->loggedUser);
        $news->saveNews();

        unset($news);
        $this->view->redirect(SimpleRouter::getLink('News.NewsAdmin'));
    }

    public function editNews($newsId = null)
    {
        $this->checkUserIsAdmin();
        $news = News::fromNewsIdFactory($newsId);
        if (is_null($news)) {
            $this->view->redirect(SimpleRouter::getLink('News.NewsAdmin'));
            exit();
        }
        $this->showEditForm($news);
    }

    public function createNews()
    {
        $this->checkUserIsAdmin();

        $news = new News();
        $news->generateDefaultValues();
        $news->setAuthor($this->loggedUser);
        $this->showEditForm($news);
    }

    private function showEditForm($news)
    {
        $this->view->setVar('dateformat_jQuery', Converter::dateformat_PHP_to_jQueryUI($this->ocConfig->getDateFormat()));
        $this->view->setVar('news', $news);
        $this->view->addLocalCss(Uri::getLinkWithModificationTime('/views/news/news.css'));
        $this->view->loadJQueryUI();

        $this->view->setTemplate('news/newsAdminEdit');
        $this->view->buildView();
        exit();
    }

    private function showAdminNewsList(array $newsList)
    {
        $this->view->setVar('newsList', $newsList);
        $this->view->addLocalCss(Uri::getLinkWithModificationTime('/views/news/news.css'));

        $this->view->setTemplate('news/newsAdmin');
        $this->view->buildView();
        exit();
    }

    private function checkUserIsAdmin()
    {
        if (! $this->isUserLogged() || ! $this->loggedUser->hasNewsPublisherRole()) {
            $this->view->redirect('/');
            exit();
        }
    }
}
