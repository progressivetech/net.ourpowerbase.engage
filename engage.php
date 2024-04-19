<?php

use CRM_Engage_ExtensionUtil as E;

require_once 'engage.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function engage_civicrm_config(&$config) {
  _engage_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function engage_civicrm_install() {
  return _engage_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function engage_civicrm_enable() {
  return _engage_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function engage_civicrm_navigationMenu(&$menu) {
  _engage_civix_insert_navigation_menu($menu, 'Search', array(
    'label' => E::ts('Engage Count Search'),
    'name' => 'engage_count_search',
    'url' => 'civicrm/powerbase/engage-count-search',
    'permission' => 'access CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _engage_civix_insert_navigation_menu($menu, 'Search', array(
    'label' => E::ts('Participant Count Search'),
    'name' => 'particpant_count_search',
    'url' => 'civicrm/powerbase/participant-count-search',
    'permission' => 'access CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ));

  _engage_civix_navigationMenu($menu);
}

