 jQuery(document).ready(function ($) {
            $("#price-slider").slider({
                range: true,
                min: 1,
                max: 3000,
                values: [0, 100],
                slide: function (event, ui) {
                    $("#price_range").val(ui.values[0] + " - " + ui.values[1]);
                    $("#price-range-display").text(ui.values[0] + " - " + ui.values[1]);
                }
            });

            $("#price_range").val($("#price-slider").slider("values", 0) +
                " - " + $("#price-slider").slider("values", 1));

            $('#book-search-form').on('submit', function (e) {
                e.preventDefault();
                var formData = $(this).serialize();
                console.log(formData);
                $.ajax({
                    type: 'POST',
                    url: ajax_object.ajax_url,
                    data: 'action=library_book_search&' + formData,
                    success: function (response) {
                        $('#book-search-results').html(response);
                    }
                });
            });
        });