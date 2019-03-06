$(function() {
    $('#side-menu').metisMenu();
});

//Loads the correct sidebar on window load,
//collapses the sidebar on window resize.
$(function() {
    $(window).bind("load resize", function() {
        if ($(this).width() < 768) {
            $('div.sidebar-collapse').addClass('collapse');
        } else {
            $('div.sidebar-collapse').removeClass('collapse');
        }

        $('#page-wrapper').css('min-height', $(this).height() - $('.navbar').outerHeight());
    });

    $('.focus-text').focus(function() {
        console.log('focus --');
        $(this).closest('div').addClass('focus-t');
    });

    $('.focus-text').blur(function() {
        console.log('blur --');
        if ($('.focus-text').length > 0 && $('.focus-text').val() != '')
            $(this).closest('div').addClass('focus-t');
        else {
            $(this).closest('div').removeClass('focus-t');
        }
    });

});