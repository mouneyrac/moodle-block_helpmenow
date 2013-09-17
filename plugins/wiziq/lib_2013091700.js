helpmenow.chat.addPluginRefresh(function (response) {
    if (typeof response.wiziq !== "undefined") {
        $('#helpmenow_wiziq').html(response.wiziq);
    }
});
