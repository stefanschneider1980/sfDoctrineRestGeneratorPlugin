  /**
   * Retrieves a  collection of <?php echo $this->getModelClass() ?> objects
   * @param   sfWebRequest   $request a request object
   * @return  string
   */
  public function executeIndex(sfWebRequest $request)
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
      $params = $this->validateIndex($params);
    }
    catch (sfValidatorError $e)
    {
      $this->getResponse()->setStatusCode(406);
      return $this->handleException($e);
    }

    $this->queryExecute($params);
    $isset_pk = null;
<?php $primaryKeys = $this->getPrimaryKeys(); ?>
<?php foreach ($primaryKeys as $primaryKey): ?>
    $isset_pk = ((null === $isset_pk) || $isset_pk) && isset($params['<?php echo $primaryKey ?>']);
<?php endforeach; ?>
    if ($isset_pk && count($this->objects) == 0)
    {
      $request->setRequestFormat($format);
      $this->forward404();
    }

<?php $embed_relations = $this->configuration->getValue('get.embed_relations'); ?>
<?php foreach ($embed_relations as $embed_relation): ?>
<?php if ($this->isManyToManyRelation($embed_relation)): ?>
    $this->embedManyToMany<?php echo $embed_relation ?>($params);
<?php endif; ?><?php endforeach; ?>

    $this->formatObjects($params);

    $this->outputObjects(true);
    unset($this->objects);
  }
