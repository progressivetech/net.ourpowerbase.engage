<?php

class CRM_Engage_Form_Report_ActivityCounter extends CRM_Report_Form {

  protected $_addressField = FALSE;
  protected $_emailField = FALSE;
  protected $_summary = NULL;

  protected $_customGroupGroupBy = FALSE;

  // We're not using typical Report fields - so tell the parent class
  // so we get the $_params variable set properly - which is required
  // for saving report instances (CRM_Report_Form::beginPostProcess -
  // CRM-8532).
  public $_noFields = TRUE;

  function __construct() {
    parent::__construct();
    $this->_columns = array(
      'civicrm_activity' => array(
        'dao' => 'CRM_Activity_DAO_Activity',
        'filters' => array(
          'activity_date_time' => array(
            'default' => 'this.quarter',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'activity_type_id' => array(
            'title' => ts('Activity Type'),
            'default' => '',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE)
          ),
        ),
      ),
    );
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Activity Counter Report'));
    parent::preProcess();
  }


	function getDateWhereClause() {
		$fieldName = 'activity_date_time';
	  $field = $this->_columns['civicrm_activity']['filters'][$fieldName];
		$relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
		$from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
		$to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);
		$fromTime = CRM_Utils_Array::value("{$fieldName}_from_time", $this->_params);
		$toTime = CRM_Utils_Array::value("{$fieldName}_to_time", $this->_params);
		return $this->dateClause($field['dbAlias'], $relative, $from, $to, $field['type'], $fromTime, $toTime);
	}

  function getActivityTypeIdWhereClause() {
    $fieldName = 'activity_type_id';
    $field = $this->_columns['civicrm_activity']['filters'][$fieldName];
    $op = $this->_params[$fieldName . '_op'];
    $value = $this->_params[$fieldName . '_value'];
    $min = $max = NULL;
    return $this->whereClause($field, $op, $value, $min, $max);
  }

  function totalActivities() {
		$date_where = $this->getDateWhereClause();
    $activity_where = $this->getActivityTypeIdWhereClause();
    $sql = "SELECT COUNT(*) AS count FROM civicrm_activity AS " . $this->_aliases['civicrm_activity'] . ' WHERE ' .
      $date_where . ' AND ' . $activity_where;
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    return $dao->count;
  }
  function targetContactsBySourceContact() {
		$date_where = $this->getDateWhereClause();
    $activity_where = $this->getActivityTypeIdWhereClause();
    $activity_tbl_alias = $this->_aliases['civicrm_activity'];
    $source_record_type_id = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Source');
    $target_record_type_id = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets');
    $sql = "SELECT DISTINCT c.id, c.display_name
      FROM civicrm_activity AS $activity_tbl_alias JOIN
      civicrm_activity_contact ac ON ac.activity_id = $activity_tbl_alias.id
      JOIN civicrm_contact c ON ac.contact_id = c.id WHERE $date_where
      AND $activity_where AND record_type_id = $source_record_type_id ORDER BY c.sort_name";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $ret = array();
    while($dao->fetch()) {
      $contact_id = intval($dao->id);
      $sql = "SELECT COUNT(DISTINCT ac_target.contact_id) AS count
        FROM civicrm_activity AS $activity_tbl_alias JOIN
        civicrm_activity_contact ac_source ON
          ac_source.activity_id = $activity_tbl_alias.id AND
          ac_source.record_type_id = $source_record_type_id
        JOIN civicrm_activity_contact ac_target ON
          ac_target.activity_id = $activity_tbl_alias.id AND
          ac_target.record_type_id = $target_record_type_id
        WHERE $date_where AND $activity_where
        AND ac_source.contact_id = $contact_id";
      $count_dao = CRM_Core_DAO::executeQuery($sql);
      $count_dao->fetch();
      $ret[$dao->display_name] = $count_dao->count;
    }
    return $ret;
  }
  function activitiesBySourceContact() {
		$date_where = $this->getDateWhereClause();
    $activity_where = $this->getActivityTypeIdWhereClause();
    $activity_tbl_alias = $this->_aliases['civicrm_activity'];
    $record_type_id = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Source');
    $sql = "SELECT c.display_name, COUNT(*)
      AS count FROM civicrm_activity AS $activity_tbl_alias JOIN
      civicrm_activity_contact ac ON ac.activity_id = $activity_tbl_alias.id
      JOIN civicrm_contact c ON ac.contact_id = c.id WHERE $date_where
      AND $activity_where AND record_type_id = $record_type_id GROUP BY c.id ORDER BY c.sort_name";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $ret = array();
    while($dao->fetch()) {
      $ret[$dao->display_name] = $dao->count;
    }
    return $ret;
  }

  function totalUniqueContacts() {
		$date_where = $this->getDateWhereClause();
    $activity_where = $this->getActivityTypeIdWhereClause();
    $sql = "SELECT COUNT(DISTINCT contact_id) AS count FROM civicrm_activity AS " . $this->_aliases['civicrm_activity'] .
      ' JOIN civicrm_activity_contact ac ON ac.activity_id = ' . $this->_aliases['civicrm_activity'] . '.id WHERE ' .
      $date_where . ' AND ' . $activity_where;
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    return $dao->count;
  }

  function postProcess() {
    parent::postProcess();
		$activity_count = $this->totalActivities();
		$contact_count = $this->totalUniqueContacts();

    $template = CRM_Core_Smarty::singleton();
    $template->assign('ac_results', TRUE);
    $template->assign('activity_count', $activity_count);
    $template->assign('contact_count', $contact_count);
    $template->assign('organizersActivities', $this->activitiesBySourceContact());
    $template->assign('organizersContacts', $this->targetContactsBySourceContact());
  }

  // Required functions that we don't actually use.
  function select() {
    $select = $this->_columnHeaders = array();
    $this->_select = "SELECT 1";
  }

  function from() {
    $this->_from = "";
  }

  function where() {
    $this->_where = '';
  }
}
