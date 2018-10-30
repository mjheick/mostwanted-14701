# mostwanted-14701

Asking the Jamestown Most Wanted what is up, and then posting the new ones on Facebook

Page currently hosted at https://www.facebook.com/Chautauqua-County-NY-Bookings-Most-Wanted-928416917358590/

# Sources

## most-wanted.php

Uses the following resources, publicly accessible from http://www.sheriff.us/warrant-list

Grabs the most wanted, page by page, and posts them up to facebook.

## prisoners.php

Uses publicly accessible information from http://www.sheriff.us/prisoners

Grabs the list of prisoners that exist and posts it to facebook.

## FBLogin.php

Actual facebook routine, emulating IE7 on Windows XP to use the facebook mobile site to 
post information to a Page. Bypasses creating a Facebook App with necessary permissions 
and having Facebook approve it.

## chautauqua_sheriff.us.sql

Dataase/Table format for storing data retrieved from most-wanted & prisoners. Originally
built with MariaDB in mind, but should be compatible with MySQL 5+.
