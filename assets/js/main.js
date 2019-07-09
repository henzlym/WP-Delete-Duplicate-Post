(function ($) {


    $(document).ready(function () {

        var h1 = seconds = 0, minutes = 0, hours = 0, t;

        function addTime() {
            seconds++;
            if (seconds >= 60) {
                seconds = 0;
                minutes++;
                if (minutes >= 60) {
                    minutes = 0;
                    hours++;
                }
            }

            var textContent = (hours ? (hours > 9 ? hours : "0" + hours) : "00") + ":" + (minutes ? (minutes > 9 ? minutes : "0" + minutes) : "00") + ":" + (seconds > 9 ? seconds : "0" + seconds);
            $(document).find('#clock').html("<time>" + textContent + "</time>");
            timer();
        }
        function timer() {
            $('.timer').addClass('active');
            t = setTimeout(addTime, 1000);
        }
        function updateProgressBar(value) {
            var progressBar = $('.progress-bar');
            progressBar.css({ 'width': value + '%' });
            progressBar.attr('aria-valuenow', value);
            progressBar.html(value + '%');
        }
        function uploadNotice(action, message) {
            return `<div class="wp-dd-alert ${action}" role="alert">${message}</div>`;
        }
        function searchDatabaseCB(start, end, chunks, total, totalRows, dupesCurrentCount) {

            if ((start + chunks) < total || (start + chunks) == total) {

                var percentComplete = (start / total) * 100;
                if ((start + chunks) == total) {
                    percentComplete = (end / total) * 100;
                }
                setTimeout(function () {
                    updateProgressBar(parseInt(percentComplete));
                    searchDatabase(start, end, chunks, totalRows, dupesCurrentCount);
                }, 1000);

            }
            if ((start + chunks) > total) {
                chunks = total - start;
                end = chunks + start;
                var percentComplete = (end / total) * 100;

                if (end == total && start < total) {
                    setTimeout(function () {
                        updateProgressBar(parseInt(percentComplete));
                        searchDatabase(start, end, chunks, totalRows, dupesCurrentCount);
                    }, 1000);

                }
            }
        }

        function scanDatabase() {

            $.ajax({
                url: admin.ajaxurl,
                type: "GET",
                data: {
                    "action": "scan_duplicate_posts"
                },
                async: true,
                beforeSend: function () {
                    $('.loader-container').addClass('active');

                },
                success: function (results) {
                    var results = JSON.parse(results);
                    console.log(results);
                    $('#wp-dd-post span').html(results.posts_duplicated);
                    $('#wp-dd-found span').html(results.duplicated_posts);
                    $('.loader-container').removeClass('active');
                    $('.wp-dd-overview').addClass('active');
                },
                error: function (error) {
                    console.log(error);
                }
            });
        }
        function searchDatabase(start = 0, end = 20, chunks = 100, totalRows = null, dupesCurrentCount = null) {
            $.ajax({
                url: admin.ajaxurl,
                type: "GET",
                data: {
                    "chunks": chunks,
                    "start": start,
                    "end": end,
                    "number_of_rows": totalRows,
                    "dupes_current_count": dupesCurrentCount,
                    "action": "search_duplicate_posts"
                },
                async: true,
                success: function (results) {
                    var results = JSON.parse(results);
                    var start, end, chunks, total, totalRows, dupesCurrentCount, alerts, posts, deletePosts;

                    start = results.start;
                    end = results.end;
                    chunks = results.chunks;
                    total = results.totalRows;
                    totalRows = results.totalRows;
                    dupesCurrentCount = results.dupesCurrentCount;
                    alerts = results.alerts;

                    deletePosts = results.deletePosts;

                    if (!alerts.completed) {

                        $('.read-file-alert').html(uploadNotice(alerts.action, alerts.message));
                        searchDatabaseCB(start, end, chunks, total, totalRows, dupesCurrentCount);
                    } else {
                        clearTimeout(t);
                        $('.read-file-alert').html('');

                        var percentComplete = (total / total) * 100;

                        updateProgressBar(parseInt(percentComplete));
                        $('.timer h5').html("Process Completed!");
                        $('.timer h5').css({ 'color': '#28a745', "font-size": "1.25rem" });
                        $('.timer .timer-info').html(alerts.message);
                        $(document).find('#clock').hide();
                    }

                },
                error: function (error) {
                    clearTimeout(t);
                    console.log(error);
                }
            });
        }

        function initSearch() {
            $(document).on('click', '#start-deletion', function () {

                $(this).attr('disabled', true);
                var start = $('input[name="row_start"]').val() ? $('input[name="row_start"]').val() : 0;
                var chunks = $('input[name="row_chunks"]').val() ? $('input[name="row_chunks"]').val() : 100;
                var end = parseInt(start) + parseInt(chunks);
                var totalRows = $('input[name="row_end"]').val();

                timer();
                searchDatabase(start, end, chunks.totalRows);

            });
        }

        scanDatabase();
        initSearch();
    });




})(jQuery);