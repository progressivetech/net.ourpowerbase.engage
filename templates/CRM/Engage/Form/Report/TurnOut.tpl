{* Use the default layout *}
{include file="CRM/Report/Form.tpl"}
{$to_message}
{if $to_results}
  <h2>Summary</h2>
  <div class="to-summary-numbers">
    <div><span class="reports-header">{ts}People In Universe{/ts}</span>: <span class="to-value">{$universe_count}</spam></div>
    <div><span class="reports-header">{ts}Days{/ts}</span>: <span class="to-value">{$days_count}</spam></div>
    <div><span class="reports-header">{ts}Total Calls{/ts}</span>: <span class="to-value">{$calls_count}</spam></div>
    <div><span class="reports-header">{ts}Calls per day{/ts}</span>: <span class="to-value">{$calls_per_day}</spam></div>
  </div>
  <table class="to-summary-responses">
    <tr>
      <th class="reports-header">Answer</th>
      <th class="reports-header">Calculated Total</th>
      <th class="reports-header">Percent of Universe</th>
      <th class="reports-header">Reminders Total</th>
      <th class="reports-header">Percent</th>
    </tr>
    {foreach from=$summaryResponses item=responses}
      <tr>
        {foreach from=$responses item=response}
        <td class="to-value">{$response}</td>
        {/foreach}
      </tr>
    {/foreach}
  </table>

  <h2>Organizer Summary</h2>
  <table>
    <tr>
      <th class="reports-header">Name</th>
      <th class="reports-header">Universe</th>
      <th class="reports-header">Calls</th>
      <th class="reports-header">Days</th>
      <th class="reports-header">Calls per Day</th>
      <th class="reports-header">Yes (%)</th>
      <th class="reports-header">Maybe (%)</th>
      <th class="reports-header">No (%)</th>
      <th class="reports-header">Yes Reminders (%)</th>
      <th class="reports-header">Maybe Reminders (%)</th>
    </tr>
    {foreach from=$summaryResponsesByOrganizer item=responses}
      <tr>
        {foreach from=$responses item=response}
        <td class="to-value">{$response}</td>
        {/foreach}
      </tr>
    {/foreach}
  </table>

  <h2>Daily Summary</h2>
  {foreach from=$summaryResponsesByDay item=day}
    <h3>{$day.name}</h3>
    <table>
      <tr>
        <th class="reports-header">Name</th>
        <th class="reports-header">Universe</th>
        <th class="reports-header">Calls</th>
        <th class="reports-header">Yes (Reminders)</th>
        <th class="reports-header">Maybe (Reminders)</th>
        <th class="reports-header">No</th>
      </tr>
      {foreach from=$day.organizers item=organizer}
      <tr>
        {foreach from=$organizer item=response}
        <td class="to-value">{$response}</td>
        {/foreach}
      </tr>
      {/foreach}
    </table>
  {/foreach} 
{/if}
