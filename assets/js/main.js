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
            $(document).find('#clock').html(textContent);
            timer();
        }
        function timer() {
            t = setTimeout(addTime, 1000);
        }
        function updateProgressBar(value) {
            var progressBar = $('.progress-bar');
            progressBar.css({ 'width': value + '%' });
            progressBar.attr('aria-valuenow', value);
            progressBar.html(value + '%');
        }
        function uploadNotice(action, message) {
            return `<div class="alert alert-${action}" role="alert">${message}</div>`;
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
        function deleteDuplicatePostsCB(start, end, chunks, total) {

            if ((start + chunks) < total || (start + chunks) == total) {

                var percentComplete = (start / total) * 100;
                if ((start + chunks) == total) {
                    percentComplete = (end / total) * 100;
                }
                setTimeout(function () {
                    updateProgressBar(parseInt(percentComplete));
                    deleteDuplicatePosts(start, end, chunks, total);
                }, 1000);

            }
            if ((start + chunks) > total) {
                chunks = total - start;
                end = chunks + start;
                var percentComplete = (end / total) * 100;

                if (end == total && start < total) {
                    setTimeout(function () {
                        updateProgressBar(parseInt(percentComplete));
                        deleteDuplicatePosts(start, end, chunks, total);
                    }, 1000);

                }
            }
        }
        function deleteDuplicatePosts(start, end, chunks, totalRows) {
            $.ajax({
                url: admin.ajaxurl,
                type: "GET",
                data: {
                    "chunks": chunks,
                    "start": start,
                    "end": end,
                    "number_of_rows": totalRows,
                    "action": "delete_duplicate_posts"
                },
                async: true,
                success: function (results) {
                    var results = JSON.parse(results);
                    var start, end, chunks, total, totalRows, alerts, posts, deletePosts, postsDeleted;

                    start = results.start;
                    end = results.end;
                    chunks = results.chunks;
                    total = results.totalRows;
                    totalRows = results.totalRows;
                    alerts = results.alerts;
                    posts = results.posts;
                    deletePosts = results.deletePosts;
                    postsDeleted = results.deletePosts;

                    if (!alerts.completed) {

                        $('.read-file-alert').html(uploadNotice(alerts.action, alerts.message));
                        deleteDuplicatePostsCB(start, end, chunks, totalRows);
                    } else {
                        clearTimeout(t);
                        $('.read-file-alert').html(uploadNotice(alerts.action, alerts.message));

                    }

                },
                error: function (error) {
                    clearTimeout(t);
                    console.log(error);
                }
            });
        }
        function searchDatabase(start = 0, end = 20, chunks = 20, totalRows = null, dupesCurrentCount = null) {
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
                        $('.read-file-alert').html(uploadNotice(alerts.action, alerts.message));
                        $('.read-file-alert').after(deletePosts);

                        var percentComplete = (total / total) * 100;

                        updateProgressBar(parseInt(percentComplete));

                        if (deletePosts.length > 0) {
                            $(document).on('click', '#delete-duplicates', function () {
                                deleteDuplicatePosts(0, 20, 20, dupesCurrentCount);
                            });
                        }
                    }

                },
                error: function (error) {
                    clearTimeout(t);
                    console.log(error);
                }
            });
        }

        function initSearch() {
            $(document).on('click', '#search', function () {

                var start = $('input[name="row_start"]').val() ? $('input[name="row_start"]').val() : 0;
                var chunks = $('input[name="row_chunks"]').val() ? $('input[name="row_chunks"]').val() : 20;
                var end = parseInt(start) + parseInt(chunks);
                var totalRows = $('input[name="row_end"]').val();

                timer();
                searchDatabase(start, end, chunks.totalRows);

            });
        }

        initSearch();
    });




})(jQuery);