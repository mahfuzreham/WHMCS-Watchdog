{include file="includes/Alerts.tpl"}

<div class="panel panel-default">
    <div class="panel-heading">
        <strong>Whitelisted Paths</strong>
    </div>
    <div class="panel-body">
        {if $data}
            <table class="datatable" width="100%" cellspacing="1" cellpadding="3">
                <thead>
                <tr>
                    <th>Path</th>
                    <th>Notes</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$data item=item}
                    <tr>
                        <td><code>{$item->path|escape}</code></td>
                        <td>{$item->notes|escape}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        {else}
            <div class="alert alert-info">No whitelisted paths.</div>
        {/if}
    </div>
</div>
