
{{-- ===========  Common  ===========  --}}
@include('deploy/setup.php')

@servers(['mono' => 'nc-user@91.215.137.66', 'admin' => 'nc-user@91.215.137.66', 'api' => 'nc-user@91.215.137.66'])

{{-- =========== Deploy to production server  ===========  --}}
@task('clone-task', ['on' => $on])
    echo "============= #1 - GIT clone task  ============ ";
    mkdir -p {{ $release }}

    echo "{{ $branch }}"

    git clone --depth 1 -b {{ $branch }} "{{ $repo }}" {{ $release }}

    echo "============= #1 - GIT Repository has been cloned  ============"
@endtask

{{-- Runs a fresh installation --}}
@task('composer-task', ['on' => $on])
    echo "============= #2 - Composer task  ============"

    cd {{ $release }}

    @if ($cache && $cache == 'no')
        {{ $composer }} clear-cache
    @endif

    {{ $composer }} install --no-interaction --prefer-dist --no-scripts -q -o

    echo "============= #2 - Composer dependencies have been installed  ============"
@endtask

{{-- Updates composer, then runs a fresh installation --}}
@task('artisan-task', ['on' => $on])
    echo "============= #3 - Artisan task  ============"
    cd {{ $release }}

    ln -nfs {{ $path }}/.env .env;

    {{ $interpreter }} artisan clear-compiled --env=production;
    {{ $interpreter }} artisan optimize --env=production;
    {{ $interpreter }} artisan config:clear
    {{ $interpreter }} artisan route:clear;

    @if ($server == 'admin')
        {{ $interpreter }} artisan horizon:publish;
    @endif

    echo "============= #3 - Production dependencies have been installed  ============"
@endtask

{{-- Set permissions for various files and directories --}}
@task('chmod-task', ['on' => $on])
    echo "============= #4 - Chmod task  ============"

        chown -R {{ $user }}:{{ $group }} {{ $release }};
        chmod -R ug+rwx {{ $release }};

    echo "============= #4 - Permissions has been set  ============"
@endtask

@task('symlinks-task')
    echo "============= #5 - Symlinks task  ============"
    cd {{ $release }}
    echo " {{ $current }} ";

    ln -nfs {{ $release }} {{ $current }};

    ln -nfs {{ $storagePath }}/logs {{ $release }}/storage;
    ln -nfs {{ $storagePath }}/app {{ $release }}/storage;

    {{-- cd {{ $release }} --}}
     {{ $interpreter }} artisan storage:link

    echo "============= #5 - Symlink has been set  ============"
@endtask

{{-- Таска, которая подчищает каталог после разворачивания --}}
@task('after-task')
    {{-- Останавливаем Horizon чтоб supervisor перезапустил его --}}
    cd {{ $current }}
    {{ $interpreter }} artisan horizon:terminate

    rm -rf {{ $release }}/.git
@endtask

@task('remove-task')
    echo "============= #6 - Symlinks task - Purging old releases  ============"

    # This will list our releases by modification time and delete all but the 5 most recent.
    purging=$(ls -dt {{ $path .'/releases' }}/* | tail -n +10);

    if [ "$purging" != "" ]; then
        echo Purging release: $purging ;
        rm -rf $purging;
    else
        echo "No releases found for purging at this time";
    fi
    echo "============= #6 - Symlinks task - Purging old releases  ============"
@endtask

{{-- Run all deployment tasks --}}
@macro('deploy', ['on' => $server])
    clone-task
    composer-task
    artisan-task
    symlinks-task
    after-task
    remove-task
@endmacro
