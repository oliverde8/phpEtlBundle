jQuery(document).ready(function (){
    var id = 0;
    if (jQuery('.datagrid-header-tools').length == 0 && jQuery('.ea-new-form').length == 0) {
        jQuery('.content-body').addClass("row");
    }

    jQuery('.etl-json-input').each(function () {
        id++;
        var that = jQuery(this);

        var input = that.find('.form-widget textarea');
        input.parent().append('<div id="EtlExecution_inputOptions_container_' + id +'"></div>');

        var container = document.getElementById("EtlExecution_inputOptions_container_" + id);
        var options = {
            mode: "text",
            onChangeText: function (jsonString) {
                input.val(jsonString)
            }
        };
        var editor = new JSONEditor(container, options);
        editor.set(JSON.parse(input.val()));
        input.hide();
    });

    jQuery('.etl-json-div').each(function () {
        id++;
        var that = jQuery(this);

        var json = that.find('dt > pre');
        var container = that.append('<div id="EtlExecution_inputOptions_container_' + id +'" style="width: 100%"></div>');

        var options = {
            mode: "view"
        };
        var container = document.getElementById("EtlExecution_inputOptions_container_" + id);
        var editor = new JSONEditor(container, options);

        editor.set(JSON.parse(json.html().replaceAll('<br>', '').replaceAll('<br/>', '')));
        json.hide();
    });
})