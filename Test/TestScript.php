<?php

include_once 'TestConnection.php';

/**
 * Script 1     : Retrieve only one data entry from the 'users' table.
 * Expectation  : The function should return only one data entry from the 'users' table.
 * Remark       : -
 * Result       : Pass.
 */
function script1()
{
    $result_m = db()->table('users')->fetch();
    $result_s = db('slave')->table('users')->fetch();
    return ['default' => $result_m, 'slave' => $result_s];
}

/**
 * Script 2     : Retrieve data from the 'users' table with a specific limit.
 * Expectation  : The function should return data from the 'users' table with a limit of 3 entries.
 * Remark       : -
 * Result       : Pass.
 */
function script2()
{
    $result_m = db()->table('users')->limit(3)->get();
    $result_s = db('slave')->table('users')->limit(3)->get();
    return ['default' => $result_m, 'slave' => $result_s];
}

/**
 * Script 3     : Retrieve only one data entry from the 'users' table with condition ID is 18.
 * Expectation  : The function should return only one data entry from the 'users' table with ID 18.
 * Remark       : -
 * Result       : Pass.
 */
function script3()
{
    $result_m = db()->table('users')->where('id', 18)->fetch();
    $result_s = db('slave')->table('users')->where('id', 18)->fetch();
    return ['default' => $result_m, 'slave' => $result_s];
}

/**
 * Script 4     : Retrieve data from the 'users' table with a specific limit and ordering.
 * Expectation  : The function should return data from the 'users' table with a limit of 3 entries, ordered by the 'name' column in descending order.
 * Remark       : -
 * Result       : Pass.
 */
function script4()
{
    $result_m = db()->table('users')->orderBy('name', 'desc')->limit(3)->get();
    $result_s = db('slave')->table('users')->orderBy('name', 'desc')->limit(3)->get();
    return ['default' => $result_m, 'slave' => $result_s];
}

/**
 * Script 5     : Retrieve specific fields from the 'users' table for a particular user.
 * Expectation  : The function should fetch the 'id', 'name', 'email' fields for the user with an ID of 2.
 * Remark       : -
 * Result       : Pass.
 */
function script5()
{
    $result_m = db()->table('users')->select('id, name, email')->where('id', 2)->fetch();
    $result_s = db('slave')->table('users')->select('id, name, email')->where('id', 2)->fetch();
    return ['default' => $result_m, 'slave' => $result_s];
}

/**
 * Script 6     : Retrieve paginated data from the 'school_users' table where the 'applicant_status' is '13'.
 * Expectation  : The function should retrieve paginated data from the 'school_users' table where the 'applicant_status' is '13'.
 * Remark       : The function connects to the 'slave' database before executing the query.
 * Result       : Pass.
 */
function script6()
{
    return db('slave')
        ->table('school_users')
        ->select('id, user_id, school_identification_no')
        ->where('applicant_status', '13')
        ->where('place_of_birth', 'Selangor')
        ->paginate(1, 13);
}

/**
 * Script 7     : Fetch user profile data with associated role and permissions.
 * Expectation  : Retrieve user profile data along with the associated role and permissions.
 * Remark       : This script fetches user profile data for a specific user along with the associated role and permissions. It constructs a complex query using the database builder to efficiently fetch the required data and their relations.
 * Result       : Pass.
 */
function script7()
{
    $db = db();
    return $db->table('user_profile')
        ->select('id,user_id,role_id,is_main,profile_status')
        ->where('user_id', 4)
        ->where('is_main', 1)
        ->withOne('user', 'users', 'id', 'user_id', function ($db) {
            $db->select('id, name, email');
        })
        ->withOne('roles', 'master_roles', 'id', 'role_id', function ($db) {
            $db->select('id,role_name,role_status')
                ->where('role_status', 1)
                ->with('permission', 'system_permission', 'role_id', 'id', function ($db) {
                    $db->select('id,role_id,abilities_id,forbidden')
                        ->where('forbidden', 0)
                        ->withOne('abilities', 'system_abilities', 'id', 'abilities_id', function ($db) {
                            $db->select('id,title');
                        });
                });
        })
        ->fetch();
}

/**
 * Script 8     : Retrieve paginated data from the 'users' table with eager loading of 'profile' and 'schools'.
 * Expectation  : The function should retrieve paginated data from the 'users' table with eager loading of 'profile' and 'schools'.
 * Remark       : The function connects to the 'slave' database before executing the query.
 * Result       : Pass.
 */
function script8()
{
    $db = db();
    $result = $db->table('user_profile')
        ->select('id,user_id,role_id,is_main,profile_status')
        ->where('is_main', 1)
        ->withOne('user', 'users', 'id', 'user_id', function ($db) {
            $db->select('id, name, email');
        })
        ->withOne('roles', 'master_roles', 'id', 'role_id', function ($db) {
            $db->select('id,role_name,role_status')
                ->where('role_status', 1)
                ->with('permission', 'system_permission', 'role_id', 'id', function ($db) {
                    $db->select('id,role_id,abilities_id,forbidden')
                        ->where('forbidden', 0)
                        ->withOne('abilities', 'system_abilities', 'id', 'abilities_id', function ($db) {
                            $db->select('id,title');
                        });
                });
        })
        // ->toJson()
        ->paginate(5);

    return $result;
}

/**
 * Script 9     : Retrieve paginated user data with school information.
 * Expectation  : Retrieve users along with their associated school information in a paginated manner.
 * Remark       : This script queries the 'users' table and joins it with 'school_users' and 'schools' tables to fetch user data along with associated school details.
 * Result       : Pass.
 */
function script9()
{
    $result = db('slave')
        ->table('users')
        ->select('users.id,users.name,users.nickname,school_users.school_profile_id,schools.name AS company_name')
        ->leftJoin('school_users', '`school_users`.`user_id`', 'users.id')
        ->leftJoin('schools', '`school_users`.`school_id`', '`schools`.`id`')
        ->safeOutput()
        ->paginate(1);

    return $result;
}

/**
 * Script 9     : Retrieve paginated user data with school information.
 * Expectation  : Retrieve users along with their associated school information in a paginated manner.
 * Remark       : This script queries the 'users' table and joins it with 'school_users' and 'schools' tables to fetch user data along with associated school details.
 * Result       : Pass.
 */
function script10()
{
    // $result = db('slave')
    //     ->table('users')
    //     ->where('id', 1)
    //     ->whereDate('created_at', '2023-02-15')
    //     ->select('users.id,users.name,users.nickname,school_users.school_profile_id,schools.name AS company_name')
    //     ->leftJoin('school_users', '`school_users`.`user_id`', 'users.id')
    //     ->rightJoin('schools', '`school_users`.`school_id`', '`schools`.`id`')
    //     ->toSql();

    $db = db();
    $result = $db->table('user_profile')
        ->select('id,user_id,role_id,is_main,profile_status')
        ->where('is_main', 1)
        ->withOne('user', 'users', 'id', 'user_id', function ($db) {
            $db->select('id, name, email');
        })
        ->withOne('roles', 'master_roles', 'id', 'role_id', function ($db) {
            $db->select('id,role_name,role_status')
                ->where('role_status', 1)
                ->with('permission', 'system_permission', 'role_id', 'id', function ($db) {
                    $db->select('id,role_id,abilities_id,forbidden')
                        ->where('forbidden', 0)
                        ->withOne('abilities', 'system_abilities', 'id', 'abilities_id', function ($db) {
                            $db->select('id,title');
                        });
                });
        })
        ->toDebugSql();

    return $result;
}

$runner = runTest([
    // 'script1',
    // 'script2',
    // 'script3',
    // 'script4',
    // 'script5',
    // 'script6',
    // 'script7',
    // 'script8',
    // 'script9',
    'script10',
]);

dd($runner);
