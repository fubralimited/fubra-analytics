$(function(){

    // Init any soratbles
    $("ul.sortable").sortable({

        placeholder: '<li class="placeholder list-group-item"/>',

        onDragStart: function (item) {
            item.addClass("dragged");
            $("body").addClass("dragging");
        },

        onDrop: function (item) {

            item.removeClass("dragged").removeAttr("style");
            $("body").removeClass("dragging");

            $('ul.groups_order li').each(function( ind ){

                var id = $(this).data('id');
                
                $('form.groups_order .group_' + id).val(ind);
            });
        }

    });

    // Cahce some dom elements
    var groupFilterEl     = $('#group_filter'),
        profileFilterEl   = $('#profile_filter')
        profilesTableEl   = $('#profiles-table'),
        profileRowsEl     = $('#profiles-table tr.profile'),
        visitsFilterEl    = $('.profiles .visits-filter button'),
        selectAllEl       = $('.select-all button'),
        updateAllEl       = $('#group_apply');

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

    
    // Toggles between profiles with or without visits
    visitsFilterEl.click(function(){

        // Toggle Button state
        visitsFilterEl.toggleClass('hidden');

        // Show or hide profiles
        $('tr.visited').toggle();

        // Prevent submit
        return false;
    });

    // Check or uncheck all visible profiles
    selectAllEl.click(function(){

        // Toggle Button state
        selectAllEl.toggleClass('hidden');
        selectAllEl.parent().toggleClass('selected');

        // Check all visible buttons
        var check = selectAllEl.parent().hasClass('selected');
        $('tr.profile:visible .checkbox input').prop('checked', check);

        // Prevent submit
        return false;
    });

    // Update all visible profiles to selected group
    updateAllEl.change(function(){

        // Get value
        var val = $(this).val();

        // Update all filtered (visible) results to the selected group
        if (val !== '_ignore') $('tr.profile:visible select').val(val);
    });


});