<?php ?>
//<script>

elgg.provide("elgg.search_advanced");

elgg.search_advanced.init = function() {
    $(".elgg-search .search-input").each(function() {
        $(this)
        // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function(event) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                    $( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            source: function( request, response ) {
                $.getJSON( "/search_advanced/autocomplete", {
                    q: request.term
                }, response );
            },
            search: function() {
                // custom minLength
                var term = this.value;
                if ( term.length < 2){
                    return false;
                }

                var search_type = $(".elgg-search input[name='entity_type']").val();
                if( search_type && search_type != "user" && search_type != "group"){
                    return false;
                }
                
                return true;
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                if(ui.item.type !== "placeholder" && ui.item.href){
                    document.location.href = ui.item.href;
                } else {
                    event.preventDefault();
                    $(this).parents("form").submit();
                }
            },
            autoFocus: true
        }).data( "autocomplete" )._renderItem = function( ul, item ) {
            var list_body = "";
            list_body = item.content;
            
        
            return $( "<li></li>" )
            .data( "item.autocomplete", item )
            .append( "<a>" + list_body + "</a>" )
            .appendTo( ul );
        };
    });

    // type selection
    $(".search-advanced-type-selection > li > a").click(function(e) {
        $(this).next().show();
        e.preventDefault();
        e.stopPropagation();
    });

    $(".search-advanced-type-selection-dropdown").click(function(e) {
        e.stopPropagation();
    });

    $(".search-advanced-type-selection-dropdown a").click(function(e) {
        $(".search-advanced-type-selection > li > a").html($(this).html());

        $(".elgg-search input[name='search_type']").attr("disabled", "disabled");
        $(".elgg-search input[name='entity_type']").attr("disabled", "disabled").val("");
        $(".elgg-search input[name='entity_subtype']").attr("disabled", "disabled").val("");
        
        var rel = $(this).attr("rel");
        
        if (rel) {
            $(".elgg-search input[name='search_type']").val("entities").removeAttr("disabled");

            var input_vals = rel.split(" ");
            
            if (input_vals[0]) {
                if (input_vals[0] == "tags") {
                    $(".elgg-search input[name='search_type']").val(input_vals[0]);
                } else {
                    $(".elgg-search input[name='entity_type']").val(input_vals[0]).removeAttr("disabled");
                }
            }

            if (input_vals[1]) {
                $(".elgg-search input[name='entity_subtype']").val(input_vals[1]).removeAttr("disabled");
            }
        }
        
        $(".search-advanced-type-selection-dropdown").hide();
    });

    $(document).click(function() {
        $(".search-advanced-type-selection-dropdown").hide();
    });

    $(".search-advanced-widget-search-form").live("submit", function(e) {
        var $target = $(this).next();
        
        var $loader = $('#elgg-widget-loader').clone();
        $loader.attr('id', '#elgg-widget-active-loader');
        $loader.removeClass('hidden');
        $target.html($loader);

        $target.load($(this).attr("action"), $(this).serialize()).addClass("mtm");
        e.preventDefault();
    });
}

elgg.register_hook_handler('init', 'system', elgg.search_advanced.init);
