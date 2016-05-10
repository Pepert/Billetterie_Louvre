$(function() {
    var today = new Date();
    var dd = today.getDate();
    var mm = today.getMonth()+1;
    var yyyy = today.getFullYear();

    if(dd<10){
        dd='0'+dd
    }

    if(mm<10){
        mm='0'+mm
    }
    today = yyyy+'-'+mm+'-'+dd;
    $("#user_visit_day").attr('value', today);

    $(function() {
        $.datepicker.setDefaults($.datepicker.regional[ "fr" ]);
        $("#user_visit_day").datepicker({ dateFormat: 'yy-mm-dd' });
    });

    $("input[id='user_ticket_number']").TouchSpin({
        verticalbuttons: true,
        verticalupclass: 'glyphicon glyphicon-plus',
        verticaldownclass: 'glyphicon glyphicon-minus',
        min: 1
    });
});