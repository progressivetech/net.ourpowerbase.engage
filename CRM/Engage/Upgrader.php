<?php
use CRM_Engage_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Engage_Upgrader extends CRM_Engage_Upgrader_Base {

  /**
   * Update existing sites which had a default survey option group
   * that was created by the civicrm_engage module.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1000() {
    // This has to run before the modules are reconciled to avoid a conflict.
    
    // Check for the presence of the old option group.
    $sql = "SELECT id FROM civicrm_option_group WHERE name = 'civicrm_survey_default_results_set_options'";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    if ($dao->id) {
      // Now insert a record into civicrm_managed so the reconcile script doesn't try to do it.
      $sql = "INSERT INTO civicrm_managed SET module = 'net.ourpowerbase.engage', name = 'civicrm_survey_default_results_set_options',
        entity_type = 'OptionGroup', entity_id = %0";
      CRM_Core_DAO::executeQuery($sql, [ 0 => [ $dao->id, 'Integer' ] ]);
    }
    return TRUE;
  }
}
