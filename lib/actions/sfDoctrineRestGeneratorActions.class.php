<?php

class sfDoctrineRestGeneratorActions extends sfActions
{
  /**
   * Creates a <?php echo $this->getModelClass() ?> object
   * @param   sfWebRequest   $request a request object
   * @return  string
   */
  public function executeCreate(sfWebRequest $request)
  {
    $this->forward404Unless($request->isMethod(sfRequest::POST));
    $content = $this->getContent();

    try
    {
      $params = $this->validateCreate($content);
    }
    catch (sfValidatorError $e)
    {
      return $this->handleException($e);
    }

    $this->object = $this->createObject();
    $this->updateObjectFromParameters($params);
    $this->getResponse()->setStatusCode(201);
    $this->doSave();
    $this->getResponse()->setHttpHeader('Location', $this->getUrlForAction('show', false));
    return sfView::SUCCESS;
  }

  /**
   * Retrieves an object
   * @param   sfWebRequest   $request a request object
   * @return  string
   */
  public function executeShow(sfWebRequest $request)
  {
    $this->forward404Unless($request->isMethod(sfRequest::GET));
    $params = $request->getParameterHolder()->getAll();

    // notify an event before the action's body starts
    $this->dispatcher->notify(new sfEvent($this, 'sfDoctrineRestGenerator.get.pre', array('params' => $params)));

    $this->setTemplate('index');
    $params = $this->cleanupParameters($params);

    try
    {
      $format = $this->getFormat();
      $params = $this->validateShow($params);
    }
    catch (sfValidatorError $e)
    {
      $this->getResponse()->setStatusCode(406);
      return $this->handleException($e);
    }

    $this->queryFetchOne($params);
    if ($format = $this->getFormat()) {
      $request->setRequestFormat($format);
    }
    $this->forward404Unless(is_array($this->objects[0]));

    $this->formatObjects($params);

    $this->outputObjects(false);
  }

  public function executeUpdate(sfWebRequest $request)
  {
    $this->forward404Unless($request->isMethod(sfRequest::PUT));
    $content = $this->getContent();

    try
    {
      $params = $this->validateUpdate($content);
    }
    catch (sfValidatorError $e)
    {
      $this->getResponse()->setStatusCode(406);
      return $this->handleException($e);
    }

    // retrieve the object
    $requestparams = $request->getParameterHolder()->getAll();
    $requestparams = $this->cleanupParameters($requestparams);
    $this->object = $this->query($requestparams)->fetchOne();
    $this->forward404Unless($this->object);

    // update and save it
    $this->updateObjectFromParameters($params);

    return $this->doSave();
  }

  /**
   * Removes an object, based on its
   * primary key
   * @param   sfWebRequest   $request a request object
   * @return  string
   */
  public function executeDelete(sfWebRequest $request)
  {
    $this->forward404Unless($request->isMethod(sfRequest::DELETE));

    // retrieve the object
    $requestparams = $request->getParameterHolder()->getAll();
    $requestparams = $this->cleanupParameters($requestparams);
    $this->object = $this->query($requestparams)->fetchOne();
    $this->forward404Unless($this->object);

    $this->object->delete();
    return sfView::NONE;
  }

  /**
   * Allows to change configure some fields of the response, based on the
   * generator.yml configuration file. Supported configuration directives are
   * "date_format" and "tag_name"
   *
   * @return  void
   */
  protected function configureFields()
  {
    //foreach ($this->objects as &$obj)
    //{
    //  ...
    //}
  }

  protected function createObject()
  {
    return new $this->model();
  }

  protected function doSave()
  {
    $this->object->save();

    $this->objects = array($this->object->toArray(false));
    $this->formatObjects(array());
    $this->outputObjects(false);
    $this->setTemplate('index');

    return sfView::SUCCESS;
  }

  protected function getContent()
  {
    $request = $this->getRequest();
    $content = $request->getContent();

    // Restores backward compatibility. Content can be the HTTP request full body, or a form encoded "content" var.
    if (strpos($content, 'content=') === 0)
    {
      $content = $request->getParameter('content');
    }
    if ($content === false)
    {
      $content = $request->getPostParameter('content'); // Last chance to get the content!
    }

    return $content;
  }

  /**
   * Returns the list of validators for a create request.
   * @return  array  an array of validators
   */
  public function getCreatePostValidators()
  {
    return array();
  }

  /**
   * Returns the list of validators for a get request.
   * @return  array  an array of validators
   */
  public function getIndexPostValidators()
  {
    return array();
  }

  protected function getPaginationValidators()
  {
    return array();
  }

  protected function getSortValidators()
  {
    return array();
  }

  /**
   * Returns the list of validators for an update request.
   * @return  array  an array of validators
   */
  public function getUpdatePostValidators()
  {
    return $this->getCreatePostValidators() ;
  }

  /**
   * Returns the list of validators for an update request.
   * @return  array  an array of validators
   */
  public function getUpdateValidators()
  {
    return $this->getCreateValidators();
  }

  protected function formatValidatorErrorSchema(sfValidatorErrorSchema $errors)
  {
    $error = array();
    foreach ($errors->getErrors() as $name => $err)
    {
      $error[] = $this->formatValidatorError($name, $err);
    }
    return $error;
  }
  protected function formatValidatorError($name, sfValidatorError $err)
  {
    $error = array(
      'field' => $name,
      'message' => $err->getMessage(),
    );
    if ($err instanceof sfValidatorErrorSchema) {
      if (count($err->getNamedErrors()) == 0 && count($err->getGlobalErrors()) == 1) {
        $e = $err->getErrors();
        $error['parameters'] = $e[0]->getArguments(true);
      } else {
        $error['errors'] = $this->formatValidatorErrorSchema($err);
      }
    } else {
      $error['parameters'] = $err->getArguments(true);
    }
    return $error;
  }
  /**
   * Handle an exception
   * @param  Exception  exception
   * @return sfView::SUCCESS;
   */
  public function handleException(Exception $e)
  {
    $this->getResponse()->setStatusCode(406);
    $serializer = $this->getSerializer();
    $this->getResponse()->setContentType($serializer->getContentType());
    if ($e instanceof sfValidatorErrorSchema)
    {
      $err = $this->formatValidatorErrorSchema($e);
      $this->output = $serializer->serialize($err);
      return $this->renderText($this->output);
    }
    $error = $e->getMessage();

    // event filter to enable customisation of the error message.
    $result = $this->dispatcher->filter(
      new sfEvent($this, 'sfDoctrineRestGenerator.filter_error_output'),
      $error
    )->getReturnValue();

    if ($error === $result)
    {
      $error = array(array('message' => $error));
      $this->output = $serializer->serialize($error, 'error');
    }
    else
    {
      $this->output = $serializer->serialize($result);
    }

    return $this->renderText($this->output);
  }

  /**
   * Output the objects
   *
   * @return void
   */
  protected function outputObjects($multiple = true)
  {
    $serializer = $this->getSerializer();
    $this->getResponse()->setContentType($serializer->getContentType());
    if ($multiple) {
      $this->output = $serializer->serialize($this->objects, $this->model);
    } else {
      $this->output = $serializer->serialize($this->objects[0], $this->model, false);
	 }
	 $this->getRequest()->setRequestFormat('html');
  }

  /**
   * Applies a set of validators to an array of parameters
   *
   * @param array   $params      An array of parameters
   * @param array   $validators  An array of validators
   * @throw sfException
   */
  public function postValidate($params, $validators, $prefix = '')
  {
    foreach ($params as $name => $value)
    {
      if (isset($validators[$name]))
      {
        if (is_array($validators[$name]))
        {
          // validator for a related object
          $params[$name] = $this->validate($value, $validators[$name], $prefix.$name.'.');
        }
        else
        {
          $params[$name] = $validators[$name]->clean($value);
        }
      }
    }
    return $params;
  }

  public function query($params)
  {
    $q = $this->queryBase($params);

    $this->querySelect($q, $params);

    $this->queryEmbedRelations($q, $params);
    $this->queryEmbedRelationsCustom($q, $params);

    $this->queryPagination($q, $params);
    $this->querySort($q, $params);

    $this->queryFilterPrimaryKeys($q, $params);
    $this->queryFilters($q, $params);

    foreach ($params as $name => $value)
    {
      $q->andWhere($this->model.'.'.$name.' = ?', $value);
    }

    return $q;
  }

  public function queryBase(array &$params)
  {
    return Doctrine_Query::create()
      ->from($this->model.' '.$this->model);
  }

  /**
   * Add joins for relations specified in the "embed_relations" config
   *
   * @param  Doctrine_Query  $query   The query to add joins to
   * @param  array           &$params The filtered parameters for this request
   */
  public function queryEmbedRelations(Doctrine_Query_Abstract $query, array &$params)
  {
  }

  /**
   * Add joins for relations specified in the "embed_relations_custom" config
   *
   * @param  Doctrine_Query  $query   The query to add joins to
   * @param  array           &$params The filtered parameters for this request
   */
  public function queryEmbedRelationsCustom(Doctrine_Query_Abstract $query, array &$params)
  {
  }

  /**
   * Filter primary keys
   *
   * @param  Doctrine_Query  $query   The query to add joins to
   * @param  array           &$params The filtered parameters for this request
   */
  function queryFilterPrimaryKeys(Doctrine_Query_Abstract $q, array &$params)
  {
  }

  /**
   * Filter fields
   *
   * @param  Doctrine_Query  $query   The query to add joins to
   * @param  array           &$params The filtered parameters for this request
   */
  function queryFilters(Doctrine_Query_Abstract $q, array &$params)
  {
  }

  /**
   * Add pagination to a specified query object
   *
   * @param Doctrine_Query $query  The query to add pagination to
   * @param array &$params  The parameters
   */
  public function queryPagination(Doctrine_Query_Abstract $q, array &$params)
  {
  }

  /**
   * Add Select clause to query
   *
   * @param  Doctrine_Query  $query   The query to add joins to
   * @param  array           &$params The filtered parameters for this request
   */
  function querySelect(Doctrine_Query_Abstract $q, array &$params)
  {
  }

  /**
   * Add sort clauses from "sort_default" and "sort_custom" fields
   *
   * @param  Doctrine_Query  $query   The query to add joins to
   * @param  array           &$params The filtered parameters for this request
   */
  public function querySort(Doctrine_Query_Abstract $query, array &$params)
  {
  }

  /**
   * Execute the query for selecting a collection of objects, eventually
   * along with related objects
   *
   * @param   array   $params  an array of criterions for the selection
   */
  public function queryExecute($params)
  {
    $this->objects = $this->dispatcher->filter(
      new sfEvent(
        $this,
        'sfDoctrineRestGenerator.filter_results',
        array()
      ),
      $this->query($params)->execute(array(), Doctrine_Core::HYDRATE_ARRAY)
    )->getReturnValue();
  }

  /**
   * Execute the query for selecting an object, eventually along with related
   * objects
   *
   * @param   array   $params  an array of criterions for the selection
   */
  public function queryFetchOne($params)
  {
    $this->objects = array($this->dispatcher->filter(
      new sfEvent(
        $this,
        'sfDoctrineRestGenerator.filter_result',
        array()
      ),
      $this->query($params)->fetchOne(array(), Doctrine_Core::HYDRATE_ARRAY)
    )->getReturnValue());
  }

  protected function updateObjectFromParameters(array $parameters)
  {
    $this->object->fromArray($parameters);
  }

  protected function updateObjectFromRequest($content)
  {
    self::updateObjectFromParameters($this->parsePayload($content));
  }

  /**
   * Applies a set of validators to an array of parameters
   *
   * @param array   $params      An array of parameters
   * @param array   $validators  An array of validators
   * @return array  The cleaned parameters
   * @throw sfException
   */
  public function validate($params, $validators, $prefix = '')
  {
    $unused = array_keys($validators);

    $validatorSchema = new sfValidatorErrorSchema(new sfValidatorPass);
    foreach ($params as $name => $value)
    {
      if (!isset($validators[$name]))
      {
        $validatorSchema->addError(new sfValidatorError(new sfValidatorPass(), sprintf('Could not validate extra field')), $prefix.$name);
      }
      else
      {
        try {
          if (is_array($validators[$name]))
          {
            // validator for a related object
            $params[$name] = $this->validate($value, $validators[$name], $prefix.$name.'.');
          }
          else
          {
            $params[$name] = $validators[$name]->clean($value);
          }
        } catch (sfValidatorError $e) {
          $validatorSchema->addError($e, $prefix.$name);
        }

        unset($unused[array_search($name, $unused, true)]);
      }
    }

    // are non given values required?
    foreach ($unused as $name)
    {
      try
      {
        if (!is_array($validators[$name]))
        {
          $validators[$name]->clean(null);
        }
      }
      catch (sfValidatorError $e)
      {
        $validatorSchema->addError($e, $prefix.$name);
      }
    }

    if (count($validatorSchema) > 0) {
      throw $validatorSchema;
    }
    return $params;
  }

  /**
   * Applies the creation validators to the payload posted to the service
   *
   * @param   string   $payload  A payload string
   * @return  array    The cleaned parameters
   */
  public function validateCreate($payload)
  {
    $params = $this->parsePayload($payload);

    $validators = $this->getCreateValidators();
    $params = $this->validate($params, $validators);

    $postvalidators = $this->getCreatePostValidators();
    $params = $this->postValidate($params, $postvalidators);

    return $params;
  }

  /**
   * Applies the get validators to the constraint parameters passed to the
   * webservice
   *
   * @param   array   $params  An array of criterions used for the selection
   * @return  array   The cleaned parameters
   */
  public function validateIndex($params)
  {
    $validators = $this->getIndexValidators();
    $params = $this->validate($params, $validators);

    $postvalidators = $this->getIndexPostValidators();
    $params = $this->postValidate($params, $postvalidators);

    return $params;
  }

  /**
   * Applies the get validators to the constraint parameters passed to the
   * webservice
   *
   * @param   array   $params  An array of criterions used for the selection
   * @return  array   The cleaned parameters
   */
  public function validateShow($params)
  {
    $validators = $this->getIndexValidators();
    $params = $this->validate($params, $validators);

    $postvalidators = $this->getIndexPostValidators();
    $params = $this->postValidate($params, $postvalidators);

    return $params;
  }

  /**
   * Applies the update validators to the payload posted to the service
   *
   * @param   string   $payload  A payload string
   */
  public function validateUpdate($payload)
  {
    $params = $this->parsePayload($payload);

    $validators = $this->getUpdateValidators();
    $params = $this->validate($params, $validators);

    $postvalidators = $this->getUpdatePostValidators();
    $params = $this->postValidate($params, $postvalidators);

    return $params;
  }
}
