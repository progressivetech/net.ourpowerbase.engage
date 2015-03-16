{* Use the default layout *}
{include file="CRM/Report/Form.tpl"}
{$to_message}
{if $to_results}
  {foreach from=$data key=organizer item=results}
    <h2>Organizer: {$organizer}</h2>
    <table>
        <tr>
          <th class="reports-header">Member Code</td>
          <th class="reports-header">Universe</td>
          <th class="reports-header">Events</td>
          <th class="reports-header">Said Yes</td>
          <th class="reports-header">Attended</td>
        </tr>
        {foreach from=$results  key=constituent_type item=rows}
          <tr>
            <td>{$constituent_type}</td>
            {foreach from=$rows key=label item=number}
            <td class="to-value">{$number}</td>
            {/foreach}
          </tr>
        {/foreach}
    </table>
  {/foreach}
{/if}
