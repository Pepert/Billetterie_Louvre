$(function() {
    $('.bouton-tarif').each(function() {
        $(this).click(function()
        {
            if($(this).text() == 'Bénéficier du tarif réduit')
            {
                $(this).text('Tarif réduit sélectionné');
            }
            else
            {
                $(this).text('Bénéficier du tarif réduit')
            }
        });
    });

    $(document).bind('ready', function() {
        var elements = $("input");
        for (var i = 0; i < elements.length; i++)
        {
            elements[i].oninvalid = function(e)
            {
                if (!e.target.validity.valid)
                {
                    $(".no-empty").hide();
                    $("#empty").show();
                    var y = Math.round($('input:invalid').first().offset().top);
                    window.scrollTo(0,y-200);
                }
            };
        }
    });
});