<?php

// Create our custom group and fields
return array(
  0 => array(
    'entity' => 'OptionGroup',
    'name' => 'civicrm_survey_default_results_set_options',
    'update' => 'never',
    'params' => array (
      'version' => 3,
      'name' => 'civicrm_survey_default_results_set_options',
      'title' => 'Survey Default Results Set Options',
      'is_reserved' => '1',
      'is_active' => '1',
      'is_locked' => '0',
      'api.option_value.create' => array(
        array(
          'option_group_id' => '$value.id',
          'label' => 'Completed',
          'name' => 'civicrm_survey_results_completed',
          'value' => 'C',
          'is_default' => '0',
          'weight' => '10',
          'is_optgroup' => '0',
          'is_reserved' => '0',
          'is_active' => '1'
        ),
        array(
          'option_group_id' => '$value.id',
          'label' => 'Not Home',
          'name' => 'civicrm_survey_results_nothome',
          'value' => 'NH',
          'is_default' => '0',
          'weight' => '20',
          'is_optgroup' => '0',
          'is_reserved' => '0',
          'is_active' => '1'
        ),
        array(
          'option_group_id' => '$value.id',
          'label' => 'Moved',
          'name' => 'civicrm_survey_results_moved',
          'value' => 'MV',
          'is_default' => '0',
          'weight' => '30',
          'is_optgroup' => '0',
          'is_reserved' => '0',
          'is_active' => '1'
        ),
        array(
          'option_group_id' => '$value.id',
          'label' => 'Wrong Address',
          'name' => 'civicrm_survey_results_wrongaddress',
          'value' => 'WA',
          'is_default' => '0',
          'weight' => '40',
          'is_optgroup' => '0',
          'is_reserved' => '0',
          'is_active' => '1'
        ),
        array(
          'option_group_id' => '$value.id',
          'label' => 'Wrong Number',
          'name' => 'civicrm_survey_results_wrongnumber',
          'value' => 'WN',
          'is_default' => '0',
          'weight' => '50',
          'is_optgroup' => '0',
          'is_reserved' => '0',
          'is_active' => '1'
        ),
        array(
          'option_group_id' => '$value.id',
          'label' => 'Deceased',
          'name' => 'civicrm_survey_results_deceased',
          'value' => 'DE',
          'is_default' => '0',
          'weight' => '60',
          'is_optgroup' => '0',
          'is_reserved' => '0',
          'is_active' => '1'
        ),
      ),
    ),
  ),
);
