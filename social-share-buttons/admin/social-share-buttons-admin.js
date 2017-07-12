(function( $ ) {

	$(function() {
        Sortable.create('ssb_admin-sortable-list-container', {
            elements: $$('#ssb_admin-sortable-list-container i'),
            overlap: 'horizontal',
            constraint: 'horizontal',
            onChange: onOrderChanged
        });
	});

    function onOrderChanged(){
        var newOrder = '';
        $('#ssb_admin-sortable-list-container i').each(function(){
            if ($(this).hasClass('fa-facebook'))
                newOrder += 'f';
            else if ($(this).hasClass('fa-twitter'))
                newOrder += 't';
            else if ($(this).hasClass('fa-google-plus'))
                newOrder += 'g';
            else if ($(this).hasClass('fa-pinterest'))
                newOrder += 'p';
            else if ($(this).hasClass('fa-linkedin'))
                newOrder += 'l';
            else if ($(this).hasClass('fa-whatsapp'))
                newOrder += 'w';
        });
        $('#ssb_admin-icon-order-hidden-input').val(newOrder);
    }

})( jQuery );

