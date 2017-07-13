(function( $ ) {

	$(function() {
        Sortable.create('ssb_admin-sortable-list-container', {
            elements: $$('#ssb_admin-sortable-list-container i'),
            overlap: 'horizontal',
            constraint: 'horizontal',
            onChange: updateOrderHiddenInput
        });

        // Adjust order icons' visibility when page ready and when a checkbox is clicked
        adjustOrderIconsVisibility();
        $('.ssb_admin-visibility-checkbox').click(function(){
            adjustOrderIconsVisibility();
        });
	});

    /* Construct order string and assign it to the hidden input */
    function updateOrderHiddenInput(){
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

    /* Adjust visibility of order icons according to the visibility checkboxes */
    function adjustOrderIconsVisibility(){
        $('i[class*="facebook"]')   .css('display', $('input[name*="facebook"]').is(":checked") ? 'inline' : 'none' );
        $('i[class*="twitter"]')    .css('display', $('input[name*="twitter"]').is(":checked") ? 'inline' : 'none' );
        $('i[class*="google-plus"]').css('display', $('input[name*="google-plus"]').is(":checked") ? 'inline' : 'none' );
        $('i[class*="pinterest"]')  .css('display', $('input[name*="pinterest"]').is(":checked") ? 'inline' : 'none' );
        $('i[class*="linkedin"]')   .css('display', $('input[name*="linkedin"]').is(":checked") ? 'inline' : 'none' );
        $('i[class*="whatsapp"]')   .css('display', $('input[name*="whatsapp"]').is(":checked") ? 'inline' : 'none' );
    }

})( jQuery );

