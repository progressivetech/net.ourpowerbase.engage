<?php

use CRM_Engage_ExtensionUtil as E;

return [
   [
    'name' => 'SavedSearch_Engagement_Search',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Engagement_Search',
        'label' => E::ts('Engagement Search'),
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'display_name',
            'COUNT(Contact_ActivityContact_Activity_01.id) AS COUNT_Contact_ActivityContact_Activity_01_id',
            'AVG(Contact_ActivityContact_Activity_01.engagement_level:label) AS AVG_Contact_ActivityContact_Activity_01_engagement_level_label',
            'GROUP_CONCAT(Contact_ActivityContact_Activity_01.activity_type_id:label) AS GROUP_CONCAT_Contact_ActivityContact_Activity_01_activity_type_id_label',
          ],
          'orderBy' => [],
          'where' => [
            [
              'contact_type:name',
              '=',
              'Individual',
            ],
          ],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'Activity AS Contact_ActivityContact_Activity_01',
              'INNER',
              'ActivityContact',
              [
                'id',
                '=',
                'Contact_ActivityContact_Activity_01.contact_id',
              ],
              [
                'Contact_ActivityContact_Activity_01.record_type_id:name',
                '=',
                '"Activity Targets"',
              ],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Engagement_Search_SearchDisplay_Engagement_Search_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Engagement_Search_Table',
        'label' => E::ts('Engagement Search Table'),
        'saved_search_id.name' => 'Engagement_Search',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'sort_name',
              'ASC',
            ],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'display_name',
              'dataType' => 'String',
              'label' => E::ts('Display Name'),
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => '',
                'target' => '_blank',
              ],
              'title' => E::ts('View Contact'),
            ],
            [
              'type' => 'field',
              'key' => 'COUNT_Contact_ActivityContact_Activity_01_id',
              'dataType' => 'Integer',
              'label' => E::ts('Number of activities'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'AVG_Contact_ActivityContact_Activity_01_engagement_level_label',
              'dataType' => 'Float',
              'label' => E::ts('Average Engagement Index'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Contact_ActivityContact_Activity_01_activity_type_id_label',
              'dataType' => 'Integer',
              'label' => E::ts('List of Activity Types'),
              'sortable' => FALSE,
              'rewrite' => '',
            ],
          ],
          'actions' => TRUE,
          'classes' => [
            'table',
            'table-striped',
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ], 
  [
    'name' => 'SavedSearch_Participant_Search',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Participant_Search',
        'label' => E::ts('Participant Search'),
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'display_name',
            'COUNT(Contact_Participant_contact_id_01.id) AS COUNT_Contact_Participant_contact_id_01_id',
            'GROUP_CONCAT(DISTINCT Contact_Participant_contact_id_01.event_id.title) AS GROUP_CONCAT_Contact_Participant_contact_id_01_event_id_title',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'Participant AS Contact_Participant_contact_id_01',
              'INNER',
              [
                'id',
                '=',
                'Contact_Participant_contact_id_01.contact_id',
              ],
            ],
            [
              'Event AS Contact_Participant_contact_id_01_Participant_Event_event_id_01',
              'INNER',
              [
                'Contact_Participant_contact_id_01.event_id',
                '=',
                'Contact_Participant_contact_id_01_Participant_Event_event_id_01.id',
              ],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Participant_Search_SearchDisplay_Participant_Search_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Participant_Search_Table',
        'label' => E::ts('Participant Search Table'),
        'saved_search_id.name' => 'Participant_Search',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'sort_name',
              'ASC',
            ],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'display_name',
              'dataType' => 'String',
              'label' => E::ts('Display Name'),
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => '',
                'target' => '_blank',
              ],
              'title' => E::ts('View Contact'),
            ],
            [
              'type' => 'field',
              'key' => 'COUNT_Contact_Participant_contact_id_01_id',
              'dataType' => 'Integer',
              'label' => E::ts('Event Count'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Contact_Participant_contact_id_01_event_id_title',
              'dataType' => 'String',
              'label' => E::ts('Event list'),
              'sortable' => TRUE,
            ],
          ],
          'actions' => TRUE,
          'classes' => [
            'table',
            'table-striped',
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];

