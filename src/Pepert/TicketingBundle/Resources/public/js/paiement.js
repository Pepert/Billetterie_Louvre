$(function() {
    if (typeof price !== 'undefined')
    {
        var handler = StripeCheckout.configure({
            key: pk,
            locale: 'auto',
            token: function(token) {
                var form = $('<form action="' + url + '" method="post">' +
                    '<input type="hidden" name="stripeToken" value="' + token.id + '" />' +
                    '</form>');
                $('body').append(form);
                form.submit();
            }
        });

        $('#customButton').on('click', function(e) {
            // Open Checkout with further options
            handler.open({
                currency: "eur",
                amount: price
            });
            e.preventDefault();
        });

        // Close Checkout on page navigation
        $(window).on('popstate', function() {
            handler.close();
        });
    }
});