function syncHash() {
    var url = document.location.toString();
    if (url.match('#')) {
        $('.admin-nav a[href=#'+url.split('#')[1]+']').tab('show') ;
    }
}

function appendModal(html, id) {
    $('body').append(html);
    var modal_ele = $('#' + id);
    modal_ele.append(html);
    modal_ele.modal();
    $( "body" ).delegate( "#" + id, "hidden.bs.modal", function () {
        modal_ele.remove();
    });
}

$(function () {
    var modal_source   = $("#modal-template").html();
    var modal_template = Handlebars.compile(modal_source);

    $('.admin-nav a').click(function (e) {
      e.preventDefault();
      $(this).tab('show');
    });
    syncHash();

    $(window).on('hashchange',function(){
        syncHash();
    });

    $('.delete-user').click(function () {
        var te = $(this);
        var user_id = te.data('user-id');

        apiCall('admin/delete_user', {
            'user_id': user_id,
        }, function (new_status) {
            te.text('Deleted!');
            te.addClass('btn-disabled');
        });
    });

    $('.activate-api-modal').click(function () {
        var te = $(this);
        var username = te.data('username');
        var api_key = te.data('api-key');
        var api_active = te.data('api-active');
        var api_quota = te.data('api-quota');
        var user_id = te.data('user-id');

        var markup = `
            <div>
                <p>
                    <span>API Active</span>:

                    <code class='status-display'>
                        {{#if api_active}}True{{else}}False{{/if}}</code>

                     - <a data-user-id='{{user_id}}' data-action='toggle-api-active' class='trigger-api-modal-action btn btn-xs btn-success'>toggle</a>
                </p>
                <p>
                    <span>API Key: </span><code class='status-display'>{{api_key}}</code> - <a data-user-id='{{user_id}}' data-action='generate-new-api-key' class='trigger-api-modal-action btn btn-xs btn-danger'>reset</a>
                </p>
                <p>
                    <span>API Quota: <code>{{api_quota}}</code></span>
                </p>
            </div>
        `;
        var modal_id = "api-modal-" + username;
        var modal_context = {
            id: modal_id,
            api_key: api_key,
            api_active: api_active,
            api_quota: api_quota,
            user_id: user_id,
            title: "API Information for " + username,
            body: markup
        };
        var mt_html = modal_template(modal_context);
        var compiled_mt = Handlebars.compile(mt_html);
        mt_html = compiled_mt(modal_context);
        appendModal(mt_html, modal_id);
    });

    $('body').delegate('.trigger-api-modal-action', 'click', function () {
        var user_id = $(this).data('user-id');
        var status_display_elem = $(this).prevAll('.status-display');

        var action = $(this).data('action');

        var api_endpoint = '';

        if (action == 'toggle-api-active') {
            api_endpoint = 'admin/toggle_api_active';
        }
        else if (action == 'generate-new-api-key') {
            api_endpoint = 'admin/generate_new_api_key';
        }

        apiCall(api_endpoint, {
            'user_id': user_id,
        }, function (new_status) {
            if (action == 'toggle-api-active') {
                new_status = res_value_to_text(new_status);
            }
            status_display_elem.text(new_status);
        });
    });
});
