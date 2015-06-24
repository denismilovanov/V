# coding: utf-8

set :application, "vmeste-app.ru"
set :app_dir, '/home/test-vmeste/vmeste-app/'
# set :app_dir, '/home/sites/vmeste-app' # Для теста настроек

set :deploy_to, "#{app_dir}"

set :scm, :git
set :git_enable_submodules, true
set :repository,  "ssh://git@dev.legion.info/vmeste-app.git"
set :branch, 'master'

set :deploy_via, :copy

set :copy_exclude, ['.git/*', '.gitignore', 'Gemfile*', 'Capfile']

set :copy_remote_dir, "#{shared_path}"
set :copy_via, :scp

set :keep_releases, 10

set :user, 'test-vmeste'
# set :user, 'sites' # Для теста настроек
set :use_sudo, false
set :normalize_asset_timestamps, false
default_run_options[:pty] = true
ssh_options[:forward_agent] = true

role :web, "81.177.48.78"                          # Your HTTP server, Apache/etc
role :app, "81.177.48.78"                          # This may be the same as your `Web` server
role :db,  "81.177.48.78", :primary => true # This is where Rails migrations will run
role :db,  "81.177.48.78"

before 'deploy:finalize_update', 'deploy:composer'
after 'deploy:finalize_update', 'deploy:make_runtime_link'
after 'deploy:finalize_update', 'deploy:make_nginx_static_files'
after 'deploy:setup', 'deploy:setup_runtime'
after "deploy:restart", "deploy:cleanup"
after "deploy:make_runtime_link", "deploy:phpunit"
before "deploy:cleanup", "deploy:restart_crons"


namespace :deploy do
    task :restart do
        run "sudo service php5-fpm reload"
    end
    task :composer do
        e = `cat ./.env.test`
        run "echo '#{e}' > #{latest_release}/.env_with_slashes"
        # эта штука добавит слеш в конце каждой строки, выкидываем
        run "cat #{latest_release}/.env_with_slashes | sed -e 's/\\\\$/ /' > #{latest_release}/.env"
        run "unlink #{latest_release}/.env_with_slashes"
        run "cd #{latest_release} && composer install"
    end
    task :setup_runtime do
        #run "mkdir -p #{shared_path}/storage && chmod 775 #{shared_path}/storage"
        #run "mkdir -p #{shared_path}/system && chmod 775 #{shared_path}/system"
    end
    task :make_runtime_link do
        run "ln -s #{shared_path}/storage #{latest_release}/storage"
        #run "ln -s #{shared_path}/assets #{latest_release}/assets"
    end
    task :phpunit do
        # run "cd #{latest_release} && phpunit"
    end
    task :restart_crons do
        run "cd #{latest_release} && php artisan pids:stop-all"
    end
    task :make_nginx_static_files do
        #run "cat -s #{latest_release}/protected/views/site/index.php | sed '/^[\t\s]*$/d' > #{shared_path}/system/_static_.html"
        ##run "cat -s #{latest_release}/protected/views/site/join.php  | sed '/^[\t\s]*$/d' > #{shared_path}/system/_static_join.html"
        #run "cat -s #{latest_release}/mcss/style.css | sed '/^[\t\s]*$/d' | gzip -4 > #{latest_release}/mcss/style.css.gz "
        #run "find #{latest_release}/js -type f -print  | sed '/\.js$/!d'  | xargs -I % bash -c 'X=%; gzip -4 -c \"$X\" > \"$X.gz\"'"
        #run "find #{latest_release}/css -type f -print | sed '/\.css$/!d' | xargs -I % bash -c 'X=%; gzip -4 -c \"$X\" > \"$X.gz\"'"
        # xargs -I % bash -c 'X=%; gzip -4 -c "$X" > "$X.gz"'
        # xargs gzip -4 -k -f
    end
end
