$(function(){

    // Cahce some dom elements
    var groupFilterEl     = $('#group_filter'),
        profileFilterEl   = $('#profile_filter')
        profilesTableEl   = $('#profiles-table'),
        profileRowsEl     = $('#profiles-table tr.profile');

    // Filter on both value change (select) and keyup (typing profile filter)
    $('#group_filter, #profile_filter').on( 'change keyup', function() {

        // Get select value
        var groupFilterVal   = groupFilterEl.val(),
            profileFilterVal = profileFilterEl.val();

        // If filter is 'all' or blank show everything
        if( groupFilterVal === 'all' ) {

            profileRowsEl
                .show()
                .addClass('visible')
                .removeClass('hidden');

        // Else hide everything and show group matching rows
        } else {

            profileRowsEl
                .hide()
                .removeClass('visible')
                .addClass('hidden');

            profilesTableEl.find('tr.group-id-' + groupFilterVal)
                .show()
                .addClass('visible')
                .removeClass('hidden');
        }

        // Filter visible profile rows
        profilesTableEl.find('tr.visible').each(function(){

            // Check profile filter term is found in profile name
            if ($(this).data('name').indexOf(profileFilterVal) === -1) {

                // Hide if not found
                $(this).hide();
            };
        });
    });






});