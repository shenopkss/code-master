#!/bin/bash
database='moshen_zq'

if [[ -d src/$database ]]; then
  rm -rf src/$database
else
  mkdir src/$database
fi

php app.php laravel/start.twig $database
php-cs-fixer fix src/$database --rules=@Symfony

# models=('User' 'App' 'Platform' 'Operator' 'Project' 'ProjectType' 'AppVersion' 'AppVersionProduct' 'AppVersionPayment' 'AppSdkConfig' 'PayChannel' 'PayType' 'PaySubject' 'SdkSwitch' 'SdkSwitchTemplate' 'TestAccount' 'ProjectPermission' 'Protocol')
# models=('Operator' 'Project' 'AppVersion' 'AppSdkConfig' 'PayChannel' 'SdkSwitch' 'SdkSwitchTemplate')
# models=('App')

# models=('App' 'AppPackBatch' 'AppSubPackage' 'AppVersion' 'User')
# models=('AppVersion')
# project_name=package-api
# project_dir=/Users/shan/Projects/kingnet/${project_name}


# for model in ${models[@]};
# do
    # cp src/${database}/model/${model}.php ${project_dir}/app/Models/.
    # cp src/${database}/controller/${model}Controller.php ${project_dir}/app/Http/Controllers/.
    # cp src/${database}/trait/${model}Restable.php ${project_dir}/app/Http/Traits/.
    # cp src/${database}/observer/${model}Observer.php ${project_dir}/app/Observers/.
    # cp src/${database}/test/${model}Test.php ${project_dir}/tests/.
    # cp src/${database}/factory/${model}Factory.php ${project_dir}/database/factories/.
    # cp src/passport_app/route/${model}.php ${project_dir}/app/Http/Routes/.
# done
