<?php
class ScaffoldController extends Controller 
{

    /** 
     * Used to specify and url prefix like "/admin"
     * This way, all generated links will be preceded by this prefix : /admin/model/edit/3 instead of "/model/edit/3" 
     */
    public $url_prefix = Null;

    /**
     * default 
     * 
     * we list the model by default
     *
     * @return list_all()
     */
    public function adefault()
    {
        return $this->list_all();
    }

    /**
     * list_all 
     * 
     * select all the values for this model and list them
     * uses TURB_Objects
     *TODO Paginate
     *
     * @return html_list__all view
     */
    public function list_all ()
    {
        $models = new af_orm_Mormons($this->model_name);
        $models->paginate(isset($_GET['page']) ? $_GET['page'] : 1, isset($_GET['per_page']) ? $_GET['per_page'] : 20);
        $models->execute();
        $v = $this->getModelsView();
        $v->assign('models', $models);
        return LayoutController::wrap($v, '');
    }

    /**
     * show 
     * 
     * show the requested model
     *
     * @return void
     */
    public function show ()
    {
        if($db_object = $this->getRequestedObject())
        {
            $v = $this->getModelsView();
            $v->assign('db_object', $db_object);
            return LayoutController::wrap($v, '');
        }
        else
            return $this->list_all();
    }

    /**
     * create 
     *
     * alias for edit
     *
     * @return void
     */
    public function create ()
    {
        return $this->edit();
    }

    /**
     * edit 
     * 
     * if an id is given in __get we modify, give a filled form
     * else give an empty form
     *
     * @return void
     */
    public function edit($return_url = "", $form_url = "")
    {
        if($this->getRequestedObject() != false)
            $db_object = $this->getRequestedObject();
        else
            $db_object = $this->model;
        $v = $this->getModelsView();
        $v->assign('db_object', $db_object);
        return LayoutController::wrap($v, '');
    }

    /**
     * update 
     * 
     * update a model with the values posted from the edit form 
     *
     * @return void
     */
    public function update ()
    {
        $db_object = $this->getRequestedObject();
        if($db_object !=false && $this->isPost())
        {
            $db_object->setFromArray($_POST[strtolower($this->model_name)]);
            if(!$db_object->save(true))
            {
                $v = $this->getModelsView();
                $v->assign('db_object', $db_object);
                return LayoutController::wrap($v, '');
            }
        }
        return $this->list_all();
    }

    /**
     * insert 
     * 
     * insert a new model with the values posted from the edit form 
     *
     * @return void
     */
    public function insert ()
    {
        if($this->isPost())
        {
            $model_name = $this->model_name;
            $db_object = new $model_name();
            $db_object->setFromArray($_POST[strtolower($this->model_name)]);
            if(!$db_object->save(true))
            {
                $v = $this->getModelsView();
                $v->assign('db_object', $db_object);
                return LayoutController::wrap($v, '');
            }
        }
        return $this->list_all();
    }

    /**
     * delete 
     * 
     * deletes the requested id
     * redirects to list_all
     * TODO add confirmation
     *
     * @return void
     */
    public function delete ()
    {
        if ($this->isPost() && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == "1") {
            if($db_object = $this->getRequestedObject())
                $db_object->delete();
            return $this->list_all();
        }
        $v = $this->getModelsView();
        $db_object = $this->getRequestedObject();
        $v->assign('db_object', $db_object);
        return LayoutController::wrap($v, '');
    }

    /**
     * search 
     *
     * TODO: search function
     * 
     * @return void
     */
    public function search ()
    {
        return $this->list_all();
    }

    /**
     * getRequestedObject 
     * 
     * if __get[id] is set return the corrsponding model object 
     *
     * @return model object
     */
    private function getRequestedObject ()
    {
        if(!isset($this->__get['id']) && !is_numeric($this->__get['id']))
            return false;
        return new $this->model_name($this->__get['id']);
    }

    /**
     * getModelsView 
     * 
     * sets and caches the module view object and returns it
     * You should always use this fonction to get the View you need for your 
     * scaffold
     *
     * @return View object
     */
    private function getModelsView ()
    {
        if(!isset($this->view))
        {
            $tpl = 'scaffold/list';
            $this->view = new View($tpl);
        }
        $this->view->assign('scaffold', $this);
        $this->view->assign('model', $this->model);
        return $this->view;
    }

}

