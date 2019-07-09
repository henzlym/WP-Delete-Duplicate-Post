<div class="container border bg-white position-relative mt-5">
    <div class="row mb-3 py-2 border-bottom bg-light">
        <div class="col">
            <h1 id="wp-dd-title" class="m-0">WP Delete Duplicate Posts</h1>
        </div>
    </div>
    <div class="container-fluid position-relative p-0">
        <?php require DUPLICATE_POST_ROOT . '/admin/components/loader.php'; ?>
        <div class="wp-dd-overview row">
            <div class="col-12">
                <div class="wp-dd-stats-overview">
                    <h6 id="wp-dd-post">Number of posts duplicated:<span
                            class="badge badge-primary ml-1"><?php echo $posts_duplicated; ?></span></h6>
                    <h6 id="wp-dd-found">Duplicates found:<span
                            class="badge badge-danger ml-1"><?php echo $duplicated_posts; ?></span></h6>
                </div>
            </div>
        </div>
        <div class="wp-dd-overview row my-3">
            <div class="col">
                <div class="row">
                    <div class="col d-flex flex-column justify-content-center align-items-center">
                        <button id="start-deletion" class="btn btn-sm rounded-0 d-inline-block">Delete
                            Duplicates</button>
                        <a id="advance-settings-btn" class="btn btn-link" href="http://">
                            Advance Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container timer">
    <div class="row bg-white border border-top-0 rounded-0 mb-3 p-3">
        <div class="col">
            <h5 style="font-weight:600" class="text-center mt-2">Post Deletion in Progress</h5>
            <p class="text-center timer-info" style="color:#454545;">This may take some time. Please do not refresh
                or close your
                browser until process is
                complete.</p>
            <h3 class="text-center my-4" id="clock"><time>00:00:00</time></h3>
            <div class="progress rounded-0 my-3">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>

        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="read-file-alert d-inline-block mb-3">
            </div>
        </div>
    </div>
</div>
