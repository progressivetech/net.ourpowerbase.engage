<?php

class CRM_Engage_Form_Report_TurnOut extends CRM_Report_Form {
  private $table = 'civicrm_value_participant_info';
  private $invitation_date = 'invitation_date';
  private $invitation_response = 'invitation_response';
  private $second_call_date = 'second_call_date';
  private $second_call_response = 'second_call_response';
  private $reminder_date = 'reminder_date';
  private $reminder_response = 'reminder_response';
  private $event_ids = array();
  private $oranizers = array();

  // Temp table for keeping results.
  private $data_table = NULL;

  function __construct() {
    // Make sure civicrm_engage Drupal module is enabled, otherwise
    // non of this will work.
    if(!function_exists('civicrm_engage_civicrm_buildForm')) {
      CRM_Core_Error::fatal("You must have civicrm_engage Drupal module enabled to use this Report.");
    }

    $this->_columns = array(
      'civicrm_event' => array(
        'dao' => 'CRM_Event_DAO_Event',
        'filters' => array(
          'event_ids' => array(
            'title' => ts('Event'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->getEvents(),
          ),
        ),
      ),
     );
    parent::__construct();
  }

  function getEvents() {

    $sql = "SELECT DISTINCT ce.id, title FROM civicrm_event ce JOIN civicrm_participant cp 
      ON cp.event_id = ce.id JOIN `" . $this->table . "` pi ON cp.id = pi.entity_id 
      ORDER BY title";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $ret = array();
    while($dao->fetch()) {
      $id = $dao->id;
      $ret[$id] = $dao->title;
    }
    return $ret;

  }
  function preProcess() {
    $this->assign('reportTitle', ts('Event Turnout Report'));
    parent::preProcess();
  }

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

  function populateDataTable() {
    $this->data_table = 'civicrm_tmp_' . substr(sha1(rand()), 0, 10);
    $participant_info_table = $this->table;
    $event_ids = implode(',', $this->event_ids);
    $sql = "SELECT DISTINCT c.id AS contact_id," . $this->invitation_date . ' AS invitation_date,' . 
      $this->invitation_response . ' AS invitation_response,' . 
      $this->second_call_date . ' AS second_call_date,' . 
      $this->second_call_response . ' AS second_call_response,' . 
      $this->reminder_date . ' AS reminder_date,' . 
      $this->reminder_response . ' AS reminder_response,' . 
      'staff_responsible AS organizer ' . 
      "FROM civicrm_event e JOIN civicrm_participant p ON e.id = p.event_id ".
      "JOIN `$participant_info_table` pi ON p.id = pi.entity_id ".
      "JOIN civicrm_contact c ON c.id = p.contact_id ".
      "JOIN `civicrm_value_constituent_info` ci ON ci.entity_id = c.id ".
      "WHERE e.id IN ($event_ids)";
     

    $sql = "CREATE TEMPORARY TABLE `" . $this->data_table . "` AS " . $sql;
    CRM_Core_DAO::executeQuery($sql);

  }
  function setOrganizers() {
    $sql = "SELECT DISTINCT organizer FROM `" . $this->data_table . "`";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $this->organizers = array();
    while($dao->fetch()) {
      $this->organizers[] = $dao->organizer;
    }
  }

  function getUniverseCount($organizer = NULL) {
    $sql = "SELECT COUNT(DISTINCT contact_id) AS count FROM `" . $this->data_table . "`";
    $params = array();
    if($organizer) {
      $sql .= " WHERE organizer = %0";
      $params[0] = array($organizer, 'String');
    }
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();
    return $dao->count;
  }
  function getDays($organizer = NULL) {
    $fields = array('invitation_date', 'second_call_date', 'reminder_date');
    $dates = array();
    while(list(,$field) = each($fields)) {
      $sql = "SELECT DISTINCT `$field` AS date FROM `" . $this->data_table . "` WHERE `$field` IS NOT NULL";
      $params = array();
      if($organizer) {
        $sql .= " AND organizer = %0";
        $params[0] = array($organizer, 'String');
      }
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      while($dao->fetch()) {
        if(!in_array($dao->date, $dates)) {
          $dates[] = $dao->date;
        }
      }
    }
    return $dates;
  }
  function getCallsCount($organizer = NULL, $date = NULL) {
    $fields = array('invitation_date', 'second_call_date', 'reminder_date');
    $count = 0;
    while(list(,$field) = each($fields)) {
      $sql = "SELECT COUNT(`$field`) AS count FROM `" . $this->data_table . "` WHERE `$field` IS NOT NULL";
      $params = array();
      if($organizer) {
        $sql .= " AND organizer = %0";
        $params[0] = array($organizer, 'String');
      }
      if($date) {
        $sql .= " AND `$field` = %1";
        $params[1] = array($date, 'String');
      }
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      while($dao->fetch()) {
        $count += $dao->count;
      }
    }
    return $count;
  }

  function getCalculatedTotal($answer, $organizer = NULL, $date = NULL) {
    $params = array(0 => array($answer, 'String'));
    if($organizer) {
      $params[1] = array($organizer, 'String');
    }
    if($date) {
      $params[2] = array($date, 'String');
    }

    $sql = "SELECT COUNT(*) AS count FROM `" . $this->data_table . "` WHERE ";
    $sql .= "(";

    $sql .= "(reminder_response = '' AND second_call_response = '' AND invitation_response = %0";
    if($date) {
      $sql .= " AND invitation_date = %2";
    }
    $sql .= ') ';

    $sql .= " OR ";

    $sql .= "(reminder_response = '' AND second_call_response  = %0";
    if($date) {
      $sql .= " AND second_call_date = %2";
    }
    $sql .= ') ';

    $sql .= ' OR ';

    $sql .= "(reminder_response = %0 ";
    if($date) {
      $sql .= " AND reminder_date = %2";
    }
    $sql .= ')'; 

    $sql .= ')';

    if($organizer) {
      $sql .= " AND organizer = %1";
    }
    $dao = CRM_Core_DAO::executeQuery($sql,$params);
    $dao->fetch();
    return $dao->count;
  }
  
  function getRemindersTotal($answer, $organizer = NULL) {
    $sql = "SELECT COUNT(*) AS count FROM `" . $this->data_table . "` WHERE 
      reminder_response = %0";
    $params = array(0 => array($answer, 'String'));
    if($organizer) {
      $sql .= " AND organizer = %1";
      $params[1] = array($organizer, 'String');
    }
    $dao = CRM_Core_DAO::executeQuery($sql,$params);
    $dao->fetch();
    return $dao->count;
  }

  function setSummary($template) {
    $universe_count = $this->getUniverseCount();
    $days = $this->getDays();
    $days_count = count($days);
    $calls_count = $this->getCallsCount();
    $calls_per_day = number_format($calls_count / $days_count, 2);
    $calculated_yes = $this->getCalculatedTotal('Y'); 
    $percent_yes = number_format($calculated_yes / $universe_count, 2) * 100 . '%'; 
    $calculated_no = $this->getCalculatedTotal('N'); 
    $percent_no = number_format($calculated_no / $universe_count, 2) * 100 . '%'; 
    $calculated_maybe = $this->getCalculatedTotal('Maybe'); 
    $percent_maybe = number_format($calculated_maybe / $universe_count, 2) * 100 . '%'; 
    $reminders_yes = $this->getRemindersTotal('Y'); 
    $percent_reminders_yes = number_format($reminders_yes / $calculated_yes, 2) * 100 . '%'; 
    $reminders_no = $this->getRemindersTotal('N'); 
    $percent_reminders_no = number_format($reminders_no / $calculated_no, 2) * 100 . '%'; 
    $reminders_maybe = $this->getRemindersTotal('Maybe'); 
    $percent_reminders_maybe = number_format($reminders_maybe / $calculated_maybe, 2) * 100 . '%'; 

    $template->assign('universe_count', $universe_count);
    $template->assign('days_count', $days_count);
    $template->assign('calls_count', $calls_count);
    $template->assign('calls_per_day', $calls_per_day);

    $summaryResponses = array(
      0 => array('Yes', $calculated_yes, $percent_yes, $reminders_yes, $percent_reminders_yes), 
      1 => array('Maybe', $calculated_maybe, $percent_maybe, $reminders_maybe, $percent_reminders_maybe), 
      2 => array('No', $calculated_no, $percent_no, $reminders_no, $percent_reminders_no), 
    );

    $template->assign('to_results', TRUE);
    $template->assign('summaryResponses', $summaryResponses);
  }

  function setOrganizerSummary($template) {
    $this->setOrganizers();
    $resp = array();
    reset($this->organizers);
    while(list(, $organizer) = each($this->organizers)) {
      $universe_count = $this->getUniverseCount($organizer);
      $days = $this->getDays($organizer);
      $days_count = count($days);
      $calls_count = $this->getCallsCount($organizer);
      $calls_per_day = empty($days_count) ? '0' : number_format($calls_count / $days_count, 2);
      $calculated_yes = $this->getCalculatedTotal('Y', $organizer); 
      $percent_yes = empty($universe_count) ? '0%' : number_format($calculated_yes / $universe_count, 2) * 100 . '%'; 
      $calculated_no = $this->getCalculatedTotal('N', $organizer); 
      $percent_no = empty($universe_count) ? '0%' : number_format($calculated_no / $universe_count, 2) * 100 . '%'; 
      $calculated_maybe = $this->getCalculatedTotal('Maybe', $organizer); 
      $percent_maybe = empty($universe_count) ? '0%' : number_format($calculated_maybe / $universe_count, 2) * 100 . '%'; 
      $reminders_yes = $this->getRemindersTotal('Y', $organizer); 
      $percent_reminders_yes = empty($calculated_yes) ? '0%' : number_format($reminders_yes / $calculated_yes, 2) * 100 . '%'; 
      $reminders_no = $this->getRemindersTotal('N', $organizer); 
      $percent_reminders_no = empty($reminders_no) ? '0%' : number_format($reminders_no / $calculated_no, 2) * 100 . '%'; 
      $reminders_maybe = $this->getRemindersTotal('Maybe', $organizer); 
      $percent_reminders_maybe = empty($calculated_maybe) ? '0%' : number_format($reminders_maybe / $calculated_maybe, 2) * 100 . '%'; 

      $resp[] = array(
        $organizer, $universe_count, $calls_count, $days_count,
        $calls_per_day, "${calculated_yes} (${percent_yes})",
        "${calculated_maybe} (${percent_maybe})", "$calculated_no (${percent_no})",
        "${reminders_yes} (${percent_reminders_yes})", "${reminders_maybe} (${percent_reminders_maybe})"
      );
    }
    $template->assign('summaryResponsesByOrganizer', $resp);
  }

  function setDailySummary($template) {
    $resp = array();
    $days = $this->getDays();
    while(list(, $day) = each($days)) {
      $resp[$day] = array(
        'name' => substr($day, 0, 10)
      );
      reset($this->organizers);
      $resp[$day]['organizers'] = array();
      while(list(, $organizer) = each($this->organizers)) {
        $universe = $this->getUniverseCount($organizer);
        $calls = $this->getCallsCount($organizer, $day);
        // Don't include rows where the person made no calls.
        if($calls == 0) continue;
        $yes = $this->getCalculatedTotal('Y', $organizer, $day);
        $reminders_yes = $this->getRemindersTotal('Y', $organizer, $day);
        $maybe = $this->getCalculatedTotal('Maybe', $organizer, $day);
        $reminders_maybe = $this->getRemindersTotal('Maybe', $organizer, $day);
        $no = $this->getCalculatedTotal('N', $organizer, $day);

        $resp[$day]['organizers'][$organizer] = array($organizer, $universe, $calls,
          "${yes} (${reminders_yes})", "${maybe} (${reminders_maybe})", $no);
      }
    }
    $template->assign('summaryResponsesByDay', $resp);
  }

  function postProcess() {
    parent::postProcess();
    if(array_key_exists('event_ids_value', $_POST)) {
      $this->event_ids = $_POST['event_ids_value'];
    }
    $template = CRM_Core_Smarty::singleton();
    if(count($this->event_ids) == 0) {
      $template->assign('to_message', ts("No events were chosen."));
      return;
    }
    $this->populateDataTable();
    if($this->getUniverseCount() == 0) {
      $template->assign('to_message', ts("No turn our data is entered."));
      return;
    }

    $this->setSummary($template);
    $this->setOrganizerSummary($template);
    $this->setDailySummary($template);
  }
}
