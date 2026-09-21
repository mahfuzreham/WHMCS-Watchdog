{include file="includes/Alerts.tpl"}

<div class="collapse-group">
    <div class="panel-group" id="accordion">
        {foreach $_ADDONLANG.settings as $catvar=>$category}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="collapsed" role="button" data-toggle="collapse"
                           data-parent="#accordion"
                           href="#collapse{$category@iteration}">
                            {$_ADDONLANG.settingcategories.$catvar}
                        </a>
                    </h4>
                </div>

                <div id="collapse{$category@iteration}" class="panel-collapse collapse">
                    <div class="panel-body">
                        <table class="form">
                            <tbody>
                            {foreach from=$category name=option item=option key=setting}
                                <tr>
                                    <td class="fieldlabel lined text-left">
                                        <h3><strong>{$_ADDONLANG.settings.$catvar.$setting.title}</strong></h3>
                                    </td>

                                    <td class="fieldarea">
                                        <form action="{$modulelink}&view=Settings" method="post">
                                            <input type="hidden" name="token" value="{$csrfToken|escape}">
                                            <input type="hidden" name="category" value="{$catvar|escape}">
                                            <input type="hidden" name="setting" value="{$setting|escape}">
                                            <input type="hidden" name="heading" value="{$category@iteration}">

                                            {if $setting == 'checkFrequency'}
                                                <input type="number"
                                                       class="form-control input-300"
                                                       name="value"
                                                       value="{$data.checkFrequency|escape}"
                                                       min="1" max="2160" step="1">
                                            {elseif $setting == 'actionsTaken'}
                                                <select class="form-control" name="value[]" size="3" multiple>
                                                    <option value="neutralize" {if $data.actionsTaken.neutralize}selected{/if}>Neutralize</option>
                                                    <option value="notify" {if $data.actionsTaken.notify}selected{/if}>Notify</option>
                                                </select>
                                            {elseif $setting == 'recipients'}
                                                <textarea class="form-control" rows="3" name="value">{$data.recipients|escape}</textarea>
                                            {/if}

                                            <button type="submit" class="btn btn-primary">
                                                <i class="fad fa-save"></i> Save
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
</div>
