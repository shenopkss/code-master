#!/bin/bash
database='honghu'

if [[ -d src/$database ]]; then
  rm -rf src/$database
else
  mkdir src/$database
fi

php app.php kotlin/start.twig $database


models=('Teacher' 'Org' 'CourseChapter' 'CourseSchedule' 'CourseLesson' 'CourseComment' 'UserCourse' 'Course' 'Order' 'CourseCategory' 'OrderLine')

for model in ${models[@]};
do
# cp src/honghu/ext/model/*.kt /Users/shan/Projects/honghu/api/src/main/kotlin/com/honghu/api/ext/model/.
cp src/honghu/handler/admin/${model}Handler.kt /Users/shan/Projects/honghu/api/src/main/kotlin/com/honghu/api/handler/admin/.
cp src/honghu/model/${model}.kt /Users/shan/Projects/honghu/api/src/main/kotlin/com/honghu/api/model/.
cp src/honghu/test/admin/Test${model}.kt /Users/shan/Projects/honghu/api/src/test/kotlin/com/honghu/api/admin/
cp src/honghu/js/service/${model}.js /Users/shan/Projects/honghu/admin/src/services/.
cp src/honghu/js/model/${model}.js /Users/shan/Projects/honghu/admin/src/models/.
done
cp src/honghu/route/admin.kt /Users/shan/Projects/honghu/api/src/main/kotlin/com/honghu/api/route/.

# cp -r src/honghu/js/page/Order /Users/shan/Projects/honghu/admin/src/pages/.
# cp -r src/honghu/js/page/User /Users/shan/Projects/honghu/admin/src/pages/.