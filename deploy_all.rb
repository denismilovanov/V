# coding: utf-8

test = ENV['env'] != 'prod'
print test

if test
    set :app_dir, '/home/test-vmeste/vmeste-app/'
    set :user, 'test-vmeste'
    e = `cat ./.env.test`
else
    set :app_dir, '/home/vmeste/vmeste-app/'
    set :user, 'vmeste'
    e = `cat ./.env.prod`
end

set :application, "vmeste-app.ru"

set :deploy_to, "#{app_dir}"

set :scm, :git
set :git_enable_submodules, true
set :repository,  "ssh://git@185.49.69.108/~/vmeste.git" # ssh://git@dev.legion.info/vmeste-app.git"
set :branch, 'master'

set :deploy_via, :copy

set :copy_exclude, ['.git/*', '.gitignore', 'Gemfile*', 'Capfile']

set :copy_remote_dir, "#{shared_path}"
set :copy_via, :scp

set :keep_releases, 10

# set :user, 'sites' # Для теста настроек
set :use_sudo, false
set :normalize_asset_timestamps, false
default_run_options[:pty] = true
ssh_options[:forward_agent] = true

role :web, "95.213.161.74"                          # Your HTTP server, Apache/etc
role :app, "95.213.161.74"                          # This may be the same as your `Web` server
role :db,  "95.213.161.74", :primary => true # This is where Rails migrations will run
role :db,  "95.213.161.74"

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
