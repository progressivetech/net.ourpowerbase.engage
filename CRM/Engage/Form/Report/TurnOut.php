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
  // We're not using typical Report fields - so tell the parent class
  // so we get the $_params variable set properly - which is required
  // for saving report instances (CRM_Report_Form::beginPostProcess -
  // CRM-8532).
  public $_noFields = TRUE;

  // Temp table for keeping results.
  private $data_table = NULL;

  function __construct() {
    // Make sure civicrm_engage Drupal module is enabled, otherwise
    // non of this will work.
    if(!function_exists('civicrm_engage_civicrm_buildForm')) {
      CRM_Core_Error::fatal("You must have civicrm_engage Drupal module enabled to use this Report.");
    }

    // Initialize our table name and column names. Some installs will have different values.
    $sql = "SELECT id, table_name FROM civicrm_custom_group WHERE name =
      'Participant_Info' OR table_name like 'civicrm_value_participant_info%'";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    if($dao->id) {
      $this->table = $dao->table_name;
      // Now get our field names
      $sql = "SELECT column_name, name FROM civicrm_custom_field WHERE
        custom_group_id = %0";
      $params = array(0 => array($dao->id, 'Integer'));
      $column_dao = CRM_Core_DAO::executeQuery($sql, $params);
      $fields = array('invitation_date', 'invitation_response',
        'second_call_date', 'second_call_response', 'reminder_date',
        'reminder_response');
      while($column_dao->fetch()) {
        reset($fields);
        while(list(,$field) = each($fields)) {
          if(strtolower($column_dao->name) == $field) {
            $this->$field = $column_dao->column_name;
          }
        }
      }
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

    $sql = "SELECT DISTINCT ce.id, title, start_date FROM civicrm_event ce JOIN civicrm_participant cp 
      ON cp.event_id = ce.id JOIN `" . $this->table . "` pi ON cp.id = pi.entity_id 
      ORDER BY title";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $ret = array();
    while($dao->fetch()) {
      $start = substr($dao->start_date, 0, 10);
      $id = $dao->id;
      $ret[$id] = $dao->title . " ($start)" ;
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
      'staff_responsible AS organizer, status_id ' . 
      "FROM civicrm_event e JOIN civicrm_participant p ON e.id = p.event_id ".
      "JOIN `$participant_info_table` pi ON p.id = pi.entity_id ".
      "JOIN civicrm_contact c ON c.id = p.contact_id ".
      "JOIN `civicrm_value_constituent_info` ci ON ci.entity_id = c.id ".
      "WHERE e.id IN ($event_ids)";
     

    $sql = "CREATE TEMPORARY TABLE `" . $this->data_table . "` AS " . $sql;
    CRM_Core_DAO::executeQuery($sql);

  }
  function setOrganizers() {
    // Get custom field for staff_responsible
    $sql = "SELECT id FROM civicrm_custom_field WHERE (name = 'staff_responsible' OR 
      (name IS NULL AND column_name like 'staff_responsible%')) AND is_active = 1 ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    $organizer_map = array();
    if(!empty($dao->id)) {
      $params = array('field' => 'custom_' . $dao->id);
      $result = civicrm_api3('CustomField', 'getoptions', $params);
      if(array_key_exists('values', $result)) {
        reset($result['values']);
        while(list($key, $value) = each($result['values'])) {
          $organizer_map[$key] = $value;
        }
      }
    }
    $sql = "SELECT DISTINCT organizer FROM `" . $this->data_table . "` ORDER BY organizer";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $this->organizers = array();
    while($dao->fetch()) {
      if(array_key_exists($dao->organizer, $organizer_map)) {
        $this->organizers[$dao->organizer] = $organizer_map[$dao->organizer];
      }
      else {
        $this->organizers[$dao->organizer] = $dao->organizer;
      }
    }
  }

  // Note: $organizer might be null or empty
  function getUniverseCount($organizer = FALSE) {
    $sql = "SELECT COUNT(DISTINCT contact_id) AS count FROM `" . $this->data_table . "`";
    $params = array();
    if($organizer !== FALSE) {
      if(empty($organizer)) {
        $sql .= "WHERE organizer IS NULL OR organizer = ''";
      }
      else{
        $sql .= " WHERE organizer = %0";
        $params[0] = array($organizer, 'String');
      }
    }
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();
    return $dao->count;
  }

  function getDays($organizer = FALSE) {
    $fields = array('invitation_date', 'second_call_date', 'reminder_date');
    $dates = array();
    while(list(,$field) = each($fields)) {
      $sql = "SELECT DISTINCT `$field` AS date FROM `" . $this->data_table . "` WHERE `$field` IS NOT NULL ORDER BY date";
      $params = array();
      if($organizer !== FALSE) {
        if(empty($organizer)) {
          $sql .= " AND (organizer IS NULL OR organizer = '')";
        }
        else {
          $sql .= " AND organizer = %0";
          $params[0] = array($organizer, 'String');
        }
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

  /**
   * Return count of calls made.
   *
   * @organizer String Limit to count made by organizer
   * @date Date Limit to count made on given date
   * @contacted Bolean Limit to responses that indicate the organizer
   *  spoke to someone
   */
  function getCallsCount($organizer = FALSE, $date = NULL, $contacted = FALSE) {
    $fields = array(
      'invitation_response' => 'invitation_date',
      'second_call_response' => 'second_call_date',
      'reminder_response' => 'reminder_date'
    );
    $count = 0;
    while(list($response_field,$date_field) = each($fields)) {
      $sql = "SELECT COUNT(`$date_field`) AS count FROM `" . $this->data_table . "` WHERE `$date_field` IS NOT NULL";
      $params = array();
      if($organizer !== FALSE) {
        if(empty($organizer)) {
          $sql .= " AND (organizer IS NULL OR organizer = '')";
        }
        else {
          $sql .= " AND organizer = %0";
          $params[0] = array($organizer, 'String');
        }
      }
      if($date) {
        $sql .= " AND `$date_field` = %1";
        $params[1] = array($date, 'String');
      }
      if($contacted) {
        $responses_fragment = '';
        $responses = $this->getContactedResponses();
        while(list(,$response) = each($responses)) {
          $responses_fragment[] = '"' . CRM_Core_DAO::escapeString($response) . '"';
        }
        $sql .= " AND `$response_field` IN (" . implode(',', $responses_fragment) . ')';
      }
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      while($dao->fetch()) {
        $count += $dao->count;
      }
    }
    return $count;
  }

  function getCalculatedTotal($answer, $organizer = FALSE, $date = NULL) {
    $params = array(0 => array($answer, 'String'));
    if($organizer) {
      $params[1] = array($organizer, 'String');
    }
    if($date) {
      $params[2] = array($date, 'String');
    }

    $sql = "SELECT COUNT(*) AS count FROM `" . $this->data_table . "` WHERE ";
    $sql .= "(";

    $sql .= "(second_call_response = '' AND invitation_response = %0";
    if($date) {
      $sql .= " AND invitation_date = %2";
    }
    $sql .= ') ';

    $sql .= " OR ";

    $sql .= "(second_call_response  = %0";
    if($date) {
      $sql .= " AND second_call_date = %2";
    }
    $sql .= ') ';
    $sql .= ')';
    if($organizer !== FALSE) {
      if(empty($organizer)) {
        $sql .= " AND (organizer IS NULL OR organizer = '')";
      }
      else {
        $sql .= " AND organizer = %1";
      }
    }
    $dao = CRM_Core_DAO::executeQuery($sql,$params);
    $dao->fetch();
    return $dao->count;
  }
  
  function getAttended($organizer = FALSE) {
    $params = array();
    $sql = "SELECT COUNT(*) AS count FROM `" . $this->data_table . "` WHERE ";
    $sql .= "status_id = 2";
    if($organizer !== FALSE) {
      if(empty($organizer)) {
        $sql .= " AND (organizer IS NULL OR organizer = '')";
      }
      else {
        $sql .= " AND organizer = %0";
        $params[0] = array($organizer, 'String');
      }
    }
    $dao = CRM_Core_DAO::executeQuery($sql,$params);
    $dao->fetch();
    return $dao->count;
  }

  function getRemindersTotal($answer, $organizer = FALSE) {
    $sql = "SELECT COUNT(*) AS count FROM `" . $this->data_table . "` WHERE 
      reminder_response = %0";
    $params = array(0 => array($answer, 'String'));
    if($organizer !== FALSE) {
      if(empty($organizer)) {
        $sql .= " AND (organizer IS NULL OR organizer = '')";
      }
      else {
        $sql .= " AND organizer = %1";
         $params[1] = array($organizer, 'String');
      }
    }
    
    $dao = CRM_Core_DAO::executeQuery($sql,$params);
    $dao->fetch();
    return $dao->count;
  }

  function getContactedResponses() {
    // Should be a lookup...
    return array('Y', 'N', 'Maybe');
  }

  function setSummary($template) {
    $universe_count = $this->getUniverseCount();
    $days = $this->getDays();
    $days_count = count($days);
    $calls_count = $this->getCallsCount();
    $calls_per_day = $days_count == 0 ? 0 : number_format($calls_count / $days_count, 2);
    $contacted_count = $this->getCallsCount(NULL, NULL, TRUE);
    $contacted_per_day = $days_count == 0 ? 0 : number_format($contacted_count / $days_count, 2);
    $calculated_yes = $this->getCalculatedTotal('Y'); 
    $percent_yes = $universe_count == 0 ? 0 : number_format($calculated_yes / $universe_count, 2) * 100 . '%'; 
    $calculated_no = $this->getCalculatedTotal('N'); 
    $percent_no = $universe_count == 0 ? 0 : number_format($calculated_no / $universe_count, 2) * 100 . '%'; 
    $calculated_maybe = $this->getCalculatedTotal('Maybe'); 
    $percent_maybe = $universe_count == 0 ? 0 : number_format($calculated_maybe / $universe_count, 2) * 100 . '%'; 
    $reminders_yes = $this->getRemindersTotal('Y'); 
    $percent_reminders_yes = $calculated_yes == 0 ? 0 : number_format($reminders_yes / $calculated_yes, 2) * 100 . '%'; 
    $reminders_no = $this->getRemindersTotal('N'); 
    $percent_reminders_no = $calculated_no == 0 ? 0 : number_format($reminders_no / $calculated_no, 2) * 100 . '%'; 
    $reminders_maybe = $this->getRemindersTotal('Maybe'); 
    $percent_reminders_maybe = $calculated_maybe == 0 ? 0 : number_format($reminders_maybe / $calculated_maybe, 2) * 100 . '%'; 
    $attended_total = $this->getAttended();
    $attended_percent = $reminders_yes == 0 ? 0 : number_format($attended_total / $reminders_yes, 2) * 100 . '%';

    $template->assign('universe_count', $universe_count);
    $template->assign('days_count', $days_count);
    $template->assign('calls_count', $calls_count);
    $template->assign('contacted_count', $contacted_count);
    $template->assign('contacted_per_day', $contacted_per_day);
    $template->assign('calls_per_day', $calls_per_day);
    $template->assign('attended_total', $attended_total);
    $template->assign('attended_percent', $attended_percent);

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
    while(list($organizer, $organizer_friendly) = each($this->organizers)) {
      $universe_count = $this->getUniverseCount($organizer);
      $days = $this->getDays($organizer);
      $days_count = count($days);
      $calls_count = $this->getCallsCount($organizer);
      $contacted_count = $this->getCallsCount($organizer, NULL, TRUE);
      $calls_per_day = empty($days_count) ? '0' : number_format($calls_count / $days_count, 2);
      $contacted_per_day = empty($days_count) ? '0' : number_format($contacted_count / $days_count, 2);
      $calculated_yes = $this->getCalculatedTotal('Y', $organizer); 
      $percent_yes = empty($universe_count) ? '0%' : number_format($calculated_yes / $universe_count, 2) * 100 . '%'; 
      $calculated_no = $this->getCalculatedTotal('N', $organizer); 
      $percent_no = empty($universe_count) ? '0%' : number_format($calculated_no / $universe_count, 2) * 100 . '%'; 
      $calculated_maybe = $this->getCalculatedTotal('Maybe', $organizer); 
      $percent_maybe = empty($universe_count) ? '0%' : number_format($calculated_maybe / $universe_count, 2) * 100 . '%'; 
      $reminders_yes = $this->getRemindersTotal('Y', $organizer); 
      $percent_reminders_yes = empty($calculated_yes) ? '0%' : number_format($reminders_yes / $calculated_yes, 2) * 100 . '%'; 
      $reminders_no = $this->getRemindersTotal('N', $organizer); 
      $percent_reminders_no = empty($calculated_no) ? '0%' : number_format($reminders_no / $calculated_no, 2) * 100 . '%'; 
      $reminders_maybe = $this->getRemindersTotal('Maybe', $organizer); 
      $percent_reminders_maybe = empty($calculated_maybe) ? '0%' : number_format($reminders_maybe / $calculated_maybe, 2) * 100 . '%'; 
      $attended_total = $this->getAttended($organizer); 
      $percent_attended = empty($reminders_yes) ? '0%' : number_format($attended_total / $reminders_yes, 2) * 100 . '%'; 

      $organizer_label = NULL;
      if(!empty($organizer_friendly)) {
        $organizer_label = $organizer_friendly;
      }
      else{
        if(!empty($organizer)) {
          $organizer_label = $organizer;
        }
        else {
          $organizer_label = '[organizer not set]';
        }
      }
      $resp[] = array(
        $organizer_label, $universe_count, $calls_count, $contacted_count, $days_count,
        $calls_per_day, $contacted_per_day, "${calculated_yes} (${percent_yes})",
        "${calculated_maybe} (${percent_maybe})", "$calculated_no (${percent_no})",
        "${reminders_yes} (${percent_reminders_yes})", "${reminders_maybe} (${percent_reminders_maybe})",
        "${reminders_no} (${percent_reminders_no})", "${attended_total} (${percent_attended})"
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
      while(list($organizer, $organizer_friendly) = each($this->organizers)) {
        $universe = $this->getUniverseCount($organizer);
        $calls = $this->getCallsCount($organizer, $day);
        $contacts = $this->getCallsCount($organizer, $day, TRUE);
        // Don't include rows where the person made no calls.
        if($calls == 0) continue;
        $yes = $this->getCalculatedTotal('Y', $organizer, $day);
        $reminders_yes = $this->getRemindersTotal('Y', $organizer, $day);
        $maybe = $this->getCalculatedTotal('Maybe', $organizer, $day);
        $reminders_maybe = $this->getRemindersTotal('Maybe', $organizer, $day);
        $no = $this->getCalculatedTotal('N', $organizer, $day);
        $reminders_no = $this->getRemindersTotal('No', $organizer, $day);

        $organizer_label = NULL;
        if(!empty($organizer_friendly)) {
          $organizer_label = $organizer_friendly;
        }
        else{
          if(!empty($organizer)) {
            $organizer_label = $organizer;
          }
          else {
            $organizer_label = '[organizer not set]';
          }
        }

        $resp[$day]['organizers'][$organizer] = array($organizer_label, $universe, $calls, $contacts,
          "${yes} (${reminders_yes})", "${maybe} (${reminders_maybe})", "${no} (${reminders_no})");
      }
    }
    $template->assign('summaryResponsesByDay', $resp);
  }

  function postProcess() {
    parent::postProcess();
    if(array_key_exists('event_ids_value', $this->_params)) {
      $this->event_ids = $this->_params['event_ids_value'];
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
