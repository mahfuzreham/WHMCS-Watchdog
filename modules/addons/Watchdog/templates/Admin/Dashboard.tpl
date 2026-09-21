{include file="includes/Alerts.tpl"}

<div class="collapse-group">
    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
        {foreach $data.fileSystem as $catvar=>$category}
            <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="heading{$category@iteration}">
                    <h4 class="panel-title">
                        <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                           href="#collapse{$category@iteration}">
                            {if $_ADDONLANG.Configuration.array.auditstatus[$catvar]}
                                {$_ADDONLANG.Configuration.array.auditstatus[$catvar].title}
                            {else}
                                Status {$catvar}
                            {/if}
                            <span class="badge badge-warning">
                                <strong>{$data.statistics[$catvar]|default:0}</strong>
                            </span>
                        </a>
                    </h4>
                </div>

                <div id="collapse{$category@iteration}" class="panel-collapse collapse" role="tabpanel">
                    <div class="panel-body">
                        <table class="datatable" width="100%" cellspacing="1" cellpadding="3">
                            <thead>
                            <tr>
                                <th>{$_ADDONLANG.Dashboard.th.path}</th>
                                <th width="80">{$_ADDONLANG.global.btn.inspect}</th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach from=$category item=file}
                                <tr>
                                    <td>
                                        <code>{$file->path|escape}</code>
                                    </td>
                                    <td>
                                        <button type="button"
                                                class="btn btn-xs btn-info inspect"
                                                data-path="{$file->path|escape:'htmlall'}">
                                            {$_ADDONLANG.global.btn.inspect}
                                        </button>
                                    </td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        {/foreach}

        {if !$data.fileSystem}
            <div class="alert alert-success">
                No integrity findings.
            </div>
        {/if}
    </div>
</div>

<script>
(function ($) {
    var token = '{$csrfToken|escape:'javascript'}';

    $(document).on('click', '.inspect', function () {
        var path = $(this).data('path');

        $.post('', {
            path: path,
            inspect: 1,
            token: token
        }).done(function (data) {
            var detected = $('<div>').text(data.detected || 'Missing').html();
            var expected = $('<div>').text(data.expected || 'Missing').html();
            var pathSafe = $('<div>').text(data.path || '').html();

            $('#modalAjax .modal-title').html('<code>' + pathSafe + '</code>');
            $('#modalAjax .modal-body').html(
                '<p><strong>Detected:</strong> <code>' + detected + '</code></p>' +
                '<p><strong>Expected:</strong> <code>' + expected + '</code></p>'
            );
            $('#modalAjax .modal-submit').hide();
            $('#modalAjax .loader').hide();
            $('#modalAjax').modal('show');
        });
    });
})(jQuery);
</script>
