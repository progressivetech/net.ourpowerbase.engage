<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 =>
  array (
    'name' => 'CRM_Engage_Form_Report_ActivityCounter',
    'entity' => 'ReportTemplate',
    'params' =>
    array (
      'version' => 3,
      'label' => 'Activity Counter',
      'description' => 'Provides summary and totals of contacts with completed activities',
      'class_name' => 'CRM_Engage_Form_Report_ActivityCounter',
      'report_url' => 'net.ourpowerbase.engage/crm_engage_form_report_activitycounter',
      'component' => 'CiviCampaign',
    ),
  ),
);
