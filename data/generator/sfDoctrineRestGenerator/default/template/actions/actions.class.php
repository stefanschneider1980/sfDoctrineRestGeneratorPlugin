[?php

/**
 * <?php echo $this->getModuleName() ?> actions. REST API for the model "<?php echo $this->getModelClass() ?>"
 *
 * @package    ##PROJECT_NAME##
 * @subpackage <?php echo $this->getModuleName()."\n" ?>
 * @author     ##AUTHOR_NAME##
 *
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z xavier $
 */
class <?php echo $this->getGeneratedModuleName() ?>Actions extends <?php echo $this->getActionsBaseClass() ?>

{
  public $model = '<?php echo $this->getModelClass() ?>';
  protected $additional_params = <?php var_export($this->configuration->getValue('get.additional_params', array())); ?>;

<?php include dirname(__FILE__).'/../../parts/cleanupParameters.php' ?>

<?php include dirname(__FILE__).'/../../parts/configureFields.php' ?>

<?php include dirname(__FILE__).'/../../parts/deleteAction.php' ?>

<?php include dirname(__FILE__).'/../../parts/getCreatePostValidators.php' ?>

<?php include dirname(__FILE__).'/../../parts/getCreateValidators.php' ?>

<?php include dirname(__FILE__).'/../../parts/getFormat.php' ?>

<?php include dirname(__FILE__).'/../../parts/getIndexPostValidators.php' ?>

<?php include dirname(__FILE__).'/../../parts/getIndexValidators.php' ?>

<?php include dirname(__FILE__).'/../../parts/getSerializer.php' ?>

<?php include dirname(__FILE__).'/../../parts/getUrlForAction.php' ?>

<?php include dirname(__FILE__).'/../../parts/indexAction.php' ?>

<?php include dirname(__FILE__).'/../../parts/parsePayload.php' ?>

<?php include dirname(__FILE__).'/../../parts/query.php' ?>
<?php include dirname(__FILE__).'/../../parts/queryAdditionnal.php' ?>

<?php include dirname(__FILE__).'/../../parts/queryExecute.php' ?>

<?php include dirname(__FILE__).'/../../parts/setFieldVisibility.php' ?>

<?php include dirname(__FILE__).'/../../parts/showAction.php' ?>

<?php include dirname(__FILE__).'/../../parts/updateAction.php' ?>
}
