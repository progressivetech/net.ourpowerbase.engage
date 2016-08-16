{* Use the default layout *}
{include file="CRM/Report/Form.tpl"}
{$to_message}
{if $ac_results}
  <h2>Organization Summary</h2>
  <div class="ac-summary">
    <div><span class="reports-header">{ts}Total Activities{/ts}</span>: <span class="ac-value">{$activity_count}</spam></div>
    <div><span class="reports-header">{ts}Total Unique Contacts{/ts}</span>: <span class="ac-value">{$contact_count}</spam></div>
  </div>
  <h2>Activities by Organizer</h2>
  <table id="ac-organizers-activities">
  {foreach from=$organizersActivities key=organizer item=count}
    <tr>
      <td class="ac-organizer">{$organizer}</td>
      <td class="ac-count">{$count}</td>
    </tr>
  {/foreach}
  </table>
  <h2>Unique Contacts by Organizer</h2>
  <table id="ac-organizers-contacts">
  {foreach from=$organizersContacts key=organizer item=count}
    <tr>
      <td class="ac-organizer">{$organizer}</td>
      <td class="ac-count">{$count}</td>
    </tr>
  {/foreach}
  </table>

{/if}
