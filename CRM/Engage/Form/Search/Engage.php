<?php

/**
 * A custom contact search
 */
class CRM_Engage_Form_Search_Engage extends CRM_Contact_Form_Search_Custom_Group implements CRM_Contact_Form_Search_Interface {
  protected $_activities_temp_table;
  protected $_final_temp_table;
  protected $_constituent_info_table_name = NULL;
  protected $_staff_responsible_field_name = NULL;
  protected $_staff_responsible_options = NULL;
  protected $_constituent_type_field_name = NULL;
  protected $_constituent_type_options = NULL;

  function __construct(&$formValues) {
    parent::__construct($formValues);
    // Override columns
    $this->_columns = array(
      ts('Contact ID') => 'contact_id',
      ts('Name') => 'sort_name',
      ts('Phone') => 'phone',
      ts('Count') => 'count',
      ts('Engagement') => 'engagement'
    );
  }

  function buildForm(&$form) {
    parent::buildForm($form);
    $this->setTitle('Measure engagement (activities) by date');

    if(CRM_Core_Permission::check("access all cases and activities")) {
      $all = TRUE;
      $include_cases = TRUE;
      $activity_types = CRM_Core_PseudoConstant::activityType($all, $include_cases);
    }
    else {
      $activity_types = CRM_Core_PseudoConstant::activityType();
    }
    //Advanced Multiselect for Activity Types
    $form->add(
      'advmultiselect',
      'activity_type_id',
      ts('Activity Type'),
      $activity_types,
      false,
      array('size'  => 10,
        'style' => 'width:220px',
        'class' => 'advmultiselect'
      )
    );
    //Advanced Multiselect for Activity Statuses
    $form->add(
      'advmultiselect',
      'activity_status_id',
      ts('Activity Status'),
      CRM_Core_PseudoConstant::activityStatus( ),
      false,
      array(
        'size' => 10,
        'style' => 'width:220px',
        'class' => 'advmultiselect'
      )
    );

    $all_campaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns( NULL, NULL, FALSE, FALSE);
    $all_campaigns = $all_campaigns['campaigns'];
    $current_campaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns( NULL, NULL, FALSE, TRUE);
    $current_campaigns = $current_campaigns['campaigns'];
    $past_campaigns = array_diff($all_campaigns, $current_campaigns);
    $options = array();
    if(!empty($current_campaigns)) {
      $options['current_campaign'] = ts('Current Campaigns');
      while(list($id, $name) = each($current_campaigns)) {
        $options[$id] = "&nbsp;&nbsp;&nbsp;$name";
      }
    }
    if(!empty($past_campaigns)) {
      $options['past_campaign'] = ts('Past Campaigns');
      while(list($id, $name) = each($past_campaigns)) {
        $options[$id] = "&nbsp;&nbsp;&nbsp;$name";
      }
    }

    $form->add(
      'advmultiselect',
      'activity_campaign_id',
      ts('Campaigns'),
      $options,
      FALSE,
      array(
        'size' => 10,
        'style' => 'width:220px',
        'class' => 'advmultiselect',
      )
    );
    $form->add ('text',
      'minimum_activity_count',
      ts('Minimum Activity Count')
    );

    $form->addRule ('minimum_activity_count',
      ts('Postive Integers Only'),
      'positiveInteger'
    );

    $form->add ('text',
      'maximum_activity_count',
      ts('Maximum Activity Count')
    );

    $form->addRule ('maximum_activity_count',
      ts('Postive Integers Only'),
      'positiveInteger'
    );
    $form->add ('text',
      'minimum_engagement_level',
      ts('Minimum Engagement Level')
    );

    $form->addRule ('minimum_engagement_level',
      ts('Postive Integers Only'),
      'positiveInteger'
    );

    $form->add ('text',
      'maximum_engagement_level',
      ts('Maximum Engagement Level')
    );

    $form->addRule ('maximum_engagement_level',
      ts('Postive Integers Only'),
      'positiveInteger'
    );
    //Text fields for Date Range
    //javascript handling of fields in template adds pop-up calendar
    $form->addDate('activity_from_date',
      ts('Activities with date from...'),
      false,
      array( 'formatType' => 'custom' )
    );

    $form->addDate('activity_to_date',
      ts('...through'),
      false,
      array( 'formatType' => 'custom' )
    );

    $options = $this->get_staff_responsible_options();
    if($options) {
      //Advanced Multiselect for Staff Responsible
      $form->add  ('advmultiselect',
        'staff_responsible',
        ts('Staff Responsible'),
        $options,
        false ,
        array('size'  => 10,
          'style' => 'width:220px',
          'class' => 'advmultiselect'
        )
      );
    }
    $options = $this->get_constituent_type_options();
    if($options) {
      $form->add  ('advmultiselect',
        'constituent_type',
        ts('Constituent Type'),
        $options,
        false ,
        array('size'  => 10,
          'style' => 'width:220px',
          'class' => 'advmultiselect'
        )
      );
    }
    $form->add  ('advmultiselect',
      'participant_status_id',
      ts('Participant Status'),
      CRM_Event_PseudoConstant::participantStatus( ),
      false,
      array('size'  => 10,
        'style' => 'width:220px',
        'class' => 'advmultiselect'
      )
    );

    $base_search_elements = array('includeGroups', 'excludeGroups', 'andOr', 'includeTags', 'excludeTags');
    $our_elements = array(
      'activity_type_id',
      'activity_status_id',
      'activity_campaign_id',
      'maximum_activity_count',
      'minimum_activity_count',
      'maximum_engagement_level',
      'minimum_engagement_level',
      'activity_from_date',
      'activity_to_date',
      'staff_responsible',
      'constituent_type',
      'participant_status_id'
    );
    $combined_elements = array_merge($base_search_elements, $our_elements);
    $form->assign('elements', $combined_elements);

  }

  function from() {
    $from = parent::from();

    $from .= ' LEFT JOIN civicrm_activity_contact ac ON contact_a.id = ac.contact_id ' .
      'JOIN civicrm_activity AS activity ON ac.activity_id =  activity.id ';
    //Add table for Staff Responsible and/or constituent type
    if (!empty($this->_formValues['staff_responsible']) || !empty($this->_formValues['constituent_type'])) {
      $table = $this->get_constituent_info_table_name();
      $from .= ' INNER JOIN ' . $table . ' ci ON contact_a.id = ci.entity_id';
    }
    //Add table for participant status
    if (!empty($this->_formValues['participant_status_id'])) {
      $from .= ' INNER JOIN civicrm_participant cp ON contact_a.id = cp.contact_id ';
    }

    // Add left join for phone numbers
    $from .= ' LEFT JOIN civicrm_phone cph ON contact_a.id = cph.contact_id ';

    return $from;
  }

  function where($includeContactIDs = FALSE) {
    $where = parent::where($includeContactIDs);
    $clauses = array();

    if(!empty($this->_formValues['activity_type_id'])) {
      $activityIds = array();
      foreach($this->_formValues['activity_type_id'] as $key => $value) {
        $activityIds[$key] = intval($value);
      }
      $activityIds = implode( ',', $activityIds);
      $clauses[] = "activity.activity_type_id IN ( $activityIds )";
    }
    if(!empty($this->_formValues['activity_status_id'])) {
      $activityStatusIds = array();
      foreach($this->_formValues['activity_status_id'] as $key => $value) {
        $activityStatusIds[$key] = intval($value);
      }
      $activityStatusIds = implode( ',', $activityStatusIds);
      $clauses[] = "activity.status_id IN ( $activityStatusIds )";
    }
    if(!empty($this->_formValues['activity_campaign_id'])) {
      $activityCampaignIds = array();
      foreach($this->_formValues['activity_campaign_id'] as $key => $value) {
        $activityCampaignIds[$key] = intval($value);
      }
      $activityCampaignIds = implode( ',', $activityCampaignIds);
      $clauses[] = "activity.campaign_id IN ( $activityCampaignIds )";
    }
    if(!empty($this->_formValues['activity_from_date'])) {
      $activityFromDate = $this->_formValues['activity_from_date'];
      $activityFromDateFormatted = CRM_Utils_Date::processDate($activityFromDate);
      $clauses[] = "activity.activity_date_time >= $activityFromDateFormatted";
    }
    if(!empty($this->_formValues['activity_to_date'])) {
      $activityToDate = $this->_formValues['activity_to_date'];
      $activityToDateFormatted = CRM_Utils_Date::processDate($activityToDate);
      $clauses[] = "activity.activity_date_time <= $activityToDateFormatted";
    }
    if (!empty($this->_formValues['staff_responsible'])) {
      $staff_clauses = array();
      foreach ($this->_formValues['staff_responsible'] as $value) {
        $field_name = $this->get_staff_responsible_field_name();
        $staff_clauses[] = "ci." . $field_name . " = '" . addslashes($value) . "'";
      }
      $clauses[] = '(' . implode(' OR ', $staff_clauses) . ')';
    }
    if (!empty($this->_formValues['constituent_type'])) {
      $ct_clauses = array();
      foreach ($this->_formValues['constituent_type'] as $value) {
        $field_name = $this->get_constituent_type_field_name();
        $ct_clauses[] = "ci." . $field_name . " = '" . addslashes($value) . "'";
      }
      $clauses[] = '(' . implode(' OR ', $ct_clauses) . ')';
    }
    //Adding Participant Status Id to $where
    if(!empty($this->_formValues['participant_status_id'])) {
      $statusIds = array( );
      foreach($this->_formValues['participant_status_id'] as $key => $value) {
        $statusIds[$key] = intval($value);
      }
      $statusIds = implode( ',', $statusIds);
      $clauses[] = "cp.status_id IN ( $statusIds )";
    }

    if(count($clauses) > 0) {
      $where .= ' AND ' . implode(' AND ', $clauses);
    }
    return $where;
  }

  /**
   *
   * Construct the search query
   *
  **/
  function all( $offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = FALSE ) {
    // SELECT clause must include contact_id as an alias for civicrm_contact.id
    if ($onlyIDs) {
      $select  = 'contact_a.id as contact_id';
    } else {
      $select  = 'contact_a.id AS contact_id, contact_a.sort_name AS sort_name, COUNT(DISTINCT activity.id) '.
       'AS count, AVG(activity.engagement_level) AS engagement, GROUP_CONCAT(DISTINCT phone SEPARATOR ", ")  ';
    }

    $from  = $this->from();
    $where = $this->where($includeContactIDs);
    if (!empty($where)) {
      $where = "WHERE $where";
    }

    $sql = "SELECT $select $from $where GROUP BY contact_id ";

    $cnt_min = $this->_formValues['minimum_activity_count'];
    $cnt_max = $this->_formValues['maximum_activity_count'];
    $level_min = $this->_formValues['minimum_engagement_level'];
    $level_max = $this->_formValues['maximum_engagement_level'];

    $having_clauses = array();
    if (!empty($cnt_min)) {
      $having_clauses[] = ' COUNT(DISTINCT activity.id) >= ' . intval($cnt_min);
    }
    if (!empty($cnt_max)) {
        $having_clauses[] = ' COUNT(DISTINCT activity.id) <= ' . intval($cnt_max);
    }
    if (!empty($level_min)) {
      $having_clauses[] = ' AVG(activity.engagement_level) >= ' . intval($level_min);
    }
    if (!empty($level_max)) {
        $having_clauses[] .= ' AVG(activity.engagement_level) <= ' . intval($level_max);
    }
    if(count($having_clauses) > 0) {
      $sql .= ' HAVING ' . implode(' AND ', $having_clauses);
    }
    // Define ORDER BY for query in $sort, with default value
    if (empty($justIDs)) {
      if (!empty( $sort)) {
        if (is_string($sort)) {
          $sql .= " ORDER BY $sort ";
        } else {
          $sql .= " ORDER BY " . trim( $sort->orderBy() );
        }
      } else {
        $sql .= " ORDER BY contact_a.last_name ASC";
      }
    }

    if ($rowcount > 0 && $offset >= 0) {
      $sql .= " LIMIT $offset, $rowcount ";
    }

    // Now shove everything into a temp table so our final query doesn't use a
    // GROUP statement which breaks things if you try to use this query to build
    // a smart group (the smart group code tries to tack on additional WHERE clauses
    // which break things if we have a HAVING or ORDER BY clause.
    if(!$onlyIDs) {
      $this->_final_temp_table = 'civicrm_temp_custom_' . md5(uniqid());
      $temp_sql = 'CREATE TEMPORARY TABLE ' . $this->_final_temp_table .
        ' (contact_id int, sort_name varchar(128), count int, engagement decimal(3,2), phone varchar(255)) ENGINE=HEAP';
      CRM_Core_DAO::executeQuery($temp_sql);
      $insert_sql = "INSERT INTO " . $this->_final_temp_table . ' ' . $sql;
      CRM_Core_DAO::executeQuery($insert_sql);
      $sql = "SELECT * FROM " . $this->_final_temp_table;
    }
    else {
      $this->_final_temp_table = 'civicrm_temp_custom_' . md5(uniqid());
      $temp_sql = 'CREATE TEMPORARY TABLE ' . $this->_final_temp_table .
        ' (contact_id int) ENGINE=HEAP';
      CRM_Core_DAO::executeQuery($temp_sql);
      $insert_sql = "INSERT INTO " . $this->_final_temp_table . ' ' . $sql;
      CRM_Core_DAO::executeQuery($insert_sql);
      $sql = "SELECT contact_id FROM " . $this->_final_temp_table . ' contact_a WHERE (1)';
    }

    return $sql;
  }

  /**
    * Define the smarty template used to layout the search form and results listings.
    */
  function templateFile( ) {
    return 'CRM/Contact/Form/Search/ActivityCount.tpl';
  }

  /**
    * Depending on when installed, the staff_responsible_values could be named a number
    * of different things and be in a number of different tables.
    **/
  function set_constituent_info_values() {
    // We only need to run once to collect all the information.
    if(!is_null($this->_staff_responsible_options)) return;

    $sql = "SELECT og.name AS name, column_name, table_name FROM civicrm_custom_field cf ".
      "JOIN civicrm_option_group og ON og.id = cf.option_group_id ".
      "JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id ".
      "WHERE column_name LIKE 'staff_responsible%'";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    if($dao->N != 0) {
      $this->_staff_responsible_options = CRM_Core_OptionGroup::values($dao->name);
      $this->_staff_responsible_field_name = $dao->column_name;
      $this->_constituent_info_table_name = $dao->table_name;
    }
    $sql = "SELECT og.name AS name, column_name FROM civicrm_custom_field cf ".
      "JOIN civicrm_option_group og ON og.id = cf.option_group_id ".
      "WHERE column_name LIKE 'constituent_type%'";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    if($dao->N != 0) {
      $this->_constituent_type_options = CRM_Core_OptionGroup::values($dao->name);
      $this->_constituent_type_field_name = $dao->column_name;
    }
  }

  function get_staff_responsible_options() {
    $this->set_constituent_info_values();
    return $this->_staff_responsible_options;
  }

  function get_staff_responsible_field_name() {
    $this->set_constituent_info_values();
    return $this->_staff_responsible_field_name;
  }

  function get_constituent_type_options() {
    $this->set_constituent_info_values();
    return $this->_constituent_type_options;
  }

  function get_constituent_type_field_name() {
    $this->set_constituent_info_values();
    return $this->_constituent_type_field_name;
  }

  function get_constituent_info_table_name() {
    $this->set_constituent_info_values();
    return $this->_constituent_info_table_name;
  }
}
